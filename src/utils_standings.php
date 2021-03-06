<?php


/*  
    Copyright 2011, 2012 Mike Bell and Paul Danos

    This file is part of 20Swiss.
    
    20Swiss is free software: you can redistribute it and/or modify it under the
    terms of the GNU Affero General Public License as published by the Free
    Software Foundation, either version 3 of the License, or (at your option)
    any later version.

    20Swiss is distributed in the hope that it will be useful, but WITHOUT ANY
    WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
    FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for
    more details.

    You should have received a copy of the GNU Affero General Public License
    along with 20Swiss.  If not, see <http://www.gnu.org/licenses/>. 
*/

// TODO: rewrite class structure to utilize inheritance
//         class swiss_tourney   extends stats
//         class robin_tourney   extends stats
//         class elim_stats      extends stats
//         class sglelim_tourney extends elim_stats
//         class dblelim_tourney extends elim_stats
//       then we can offload things like elim_stats into their own utils_elim.php
//         and get rid of all these switch ($this->mode) {} statements


// stats class used to calculate standings based on game results
class stats {
    public $teams;
    function __construct($mid, $teams = array()) {
        $this->teams = array();
        $this->module = get_module($mid);

        // can initialize with a list of teams
        if ($teams) {
            foreach ($teams as $t)
                $this->add_team($t);
            $this->build_seeds();
        } 

        switch ($this->module['module_mode']) {
            case 0:  // swiss
                $this->cmp_methods = array('get_score', 'get_buchholz', 'get_berger', 'get_cumulative', 'get_seed');
                break;
            case 1:  // single-elim
                $this->cmp_methods = array('get_lasted', 'get_seed');
                break;
            case 2:  // double-elim
                $this->cmp_methods = array('get_lasted', 'get_seed');
                break;
        }
    }
    // adds team information to the stats array and initialize fields
    function add_team($team) {
        // really should just take id [and name?]
        $this->teams[$team['team_id']] = 
            array('name' => $team['team_name'], // team name
                'id'   => $team['team_id'], // team id
                'text' => $team['team_text'], // players' names
                'init' => $team['team_init'],
                // -1 if disabled, 0 if eliminated, 2 if in winners bracket, 1 if in losers bracket
                'status' => $team['is_disabled'] ? -1 : 2,
                'opponents' => array(), // list of opponents faced in order
                'faced' => array(),     // list of opponents sorted by team_id
                'result' => array(), // outcomes of games in order
                'results' => array(), // array of round data
                'score' => 0, // sum of results
                ); // table row for the view ['pos'] deleted!
    }

    // create a master tiebreak from init and rand(seed=$team['team_id'])
    function build_seeds() {
        // max+1 of the init values
        $upper = max(array_map(function($t) {return $t['init'];}, $this->teams)) + 1;

        // three arrays to sort in parallel
        $inits = array_map(function($t) use ($upper) { return ($t['init']) ? $t['init'] : $upper; }, $this->teams);
        $rands = array_map(function($t) { return(crc32($t['id'])); }, $this->teams);
        $teamids = array_keys($this->teams);

        // sort the ids [ascending] by the first two arrays, and assign seeds accordingly
        array_multisort($inits, SORT_NUMERIC, $rands, $teamids);
        foreach ($teamids as $idx => $id)
            $this->teams[$id]['seed'] = $idx + 1;
    }

    function simple_cmp($a, $b) {
        if ($a > $b) return 1;  // win
        else         return ($a < $b) ? 0 : 0.5;  // loss : tie
    }

    // ASSERT: count($game) > 0
    function add_result($game, $rnum) {
        foreach ($game as $idx => $g) {
            $scores[$g['team_id']] = $g['score'];
            $game[$idx]['name'] = $this->teams[$g['team_id']]['name'];
        }
        if (min($scores) < 0) return false;  // unfinished, so don't add result

        switch (count($game)) {
            case 1:
                $bye = array('team_id' => -1, 'score' => -1, 'name' => 'BYE');
                $this->add_team_result($game[0], $bye, $scores, $rnum);
                break;
            case 2:
                $this->add_team_result($game[0], $game[1], $scores, $rnum);
                $this->add_team_result($game[1], $game[0], $scores, $rnum);
                break;
            default:
                debug_error(201,"Unexpected number of teams in game_id {$game[0]['game_id']}","add_result");
        }
        return true;
    }

    function add_team_result($a, $b, $score, $rnum) {
        $id = $a['team_id'];
        $res = $this->simple_cmp($a['score'], $b['score']);

        $this->teams[$id]['opponents'][] = $b['team_id'];
        $this->teams[$id]['opp_name'][]  = $b['name'];
        $this->teams[$id]['result'][]    = $res;
        $this->teams[$id]['score']      += $res;
        $this->teams[$id]['games'][]     = $score;
        $this->teams[$id]['results'][]   = array(
                                                 'rnum'     => $rnum, 
                                                 'res'      => $res, 
                                                 'opp_name' => $b['name'],
                                                 'opp_id'   => $b['team_id'],
                                                 'score'    => array($a['score'], $b['score']),
                                                 'status'   => $this->teams[$id]['status'],
                                                 );

        switch ($this->module['module_mode']) {
            case 1:  // single-elim, 2=bracket 0=eliminated
                if (($res < 1) && ($this->teams[$id]['status'] > 0))
                    $this->teams[$id]['status'] = 0;
                break;
            case 2:  // double-elim, 2=winners 1=loser 0=eliminated 
                if (($res < 1) && ($this->teams[$id]['status'] > 0))
                    $this->teams[$id]['status']--;
                break;
        }
    }

    // TODO: This shouldn't be in utils_standings, but in utils_display -- unfortunately it depends on results
    //       Should possibly just add a team['first_loss'] round number [or compute that in utils_disp]
    // annoying:  here's where we set where the team goes in the respective brackets
    //            loser_idx subsequently changes if we fall on a 1mod4 round and we need to flip
    //  team['bracket_idx'] is the winners bracket original pos (vertically)
    //  team['loser_idx'] is the losers bracket original pos (adjusted for flip)
    function bracket_index($teams, $tricky = false) {
        if (! count($teams)) return;

        $bsize = pow(2, (int) ceil(log(count($teams),2)));
        array_multisort(array_map(function($t) {return $t['seed'];}, $teams), SORT_NUMERIC, $teams);
        $teams[0]['bracket_idx'] = 0; // root position for top team

        // iterate over teams in seed order with ever smaller $del hops
        foreach ($teams as $idx => $t) {
            // bracket_idx
            if ($idx > 0) {
                $c = pow(2, (int) ceil(log($idx+1,2)));
                $del = $bsize / $c;
                $teams[$idx]['bracket_idx'] = $teams[$c - ($idx+1)]['bracket_idx'] + $del;
            }

            $this->teams[$t['id']]['bracket_idx'] = $teams[$idx]['bracket_idx'];
            $this->teams[$t['id']]['loser_idx']   = 2 * $teams[$idx]['bracket_idx'];

            // for teams entering loser_bracket, flip on R2, R5, R9, ..., R[4n+1]
            //  (i.e. flip every other time) to stretch time between rematches
            foreach ($t['results'] as $r) {
                if ($r['res'] == 0) {
                    // MIKE IMMEDIATE DEBUG
                    //debug_alert($t['name']." lost on round {$r['rnum']}");
                    if (($r['rnum'] == 2) || (($r['rnum'] > 1) && ($r['rnum'] % 4 == 1)))
                        $this->teams[$t['id']]['loser_idx'] = (2*$teams[$idx]['bracket_idx']+$bsize+1) % (2*$bsize);
                    break;
                }
            }
            //MIKE IMMEDIATE DEBUG
            //debug_alert("w_idx: {$teams[$idx]['bracket_idx']}, l_idx: {$this->teams[$t['id']]['loser_idx']}");
        }
    }

    // make 'rank' equal for all teams who have the same value for ['$field']
    function level_ranks($field) {
        $teams = array_values($this->teams);
        array_multisort(array_map(function($t) {return $t['rank'];}, $teams), SORT_NUMERIC, $teams);
        
        foreach($teams as $idx => $t) {
            if (isset($prev) && ($prev[$field] == $t[$field])) {
                $this->teams[$t['id']]['rank'] = $prev['rank'];
                $t['rank'] = $prev['rank'];
            }
            $prev = $t;
        }
    }

    // Calculate standings and return array of teams sorted by Display Table Row
    function team_array() {
        $standings = array_values($this->teams);

        // TODO: I think faster would be an array_multisort with a long list of all tiebreaks...
        //       but that wouldn't have the nice side-effect of populating only those tiebreak fields
        //       that were used in the comparison
        usort($standings, array($this, 'deep_cmp'));  // sort with tie-breaks

        foreach($standings as $idx => $t)
            $this->teams[$t['id']]['rank'] = $idx+1;

        switch ($this->module['module_mode']) {
            case 0:  //swiss
                // anything?
                break;
            case 1:  // single-elim
                $this->level_ranks('lasted');
                $this->bracket_index($this->teams);
                break;
            case 2:  // and double-elim
                $this->level_ranks('lasted');
                $this->bracket_index($this->teams);
                // foreach ($lbracket as $t) {
                //
                break;
                $wbracket = array_filter($this->teams, function ($t) { return ($t['status'] == 2); });
                $this->bracket_index($wbracket, false);
                break;
            default:
                debug_error(200, "Unexpected tournament mode", "team_array");
        }

        return array_values($this->teams);
    }

    function get_score($id) {
        return $this->teams[$id]['score'];
    }
    function get_seed($id) {
        return -1 * $this->teams[$id]['seed'];
    }
    function get_lasted($id) {
        if (isset($this->teams[$id]['lasted']))  // return cached result if we have one
            return $this->teams[$id]['lasted'];

        $sum = ($this->teams[$id]['result'][0] < 1) ? 1 : 0;
        $del = 2;
        foreach ($this->teams[$id]['result'] as $r) {
            if ($r < 1)
                $del--;
            else
                $sum += $del;
        }

        $this->teams[$id]['lasted'] = $sum;  // cache for later
        return $sum;
    }

    function get_cumulative($id) {
        if (isset($this->teams[$id]['cumulative']))
            return $this->teams[$id]['cumulative'];

        if (! $this->teams[$id]['result'])
            $tbscore = 0;
        else {
            foreach ($this->teams[$id]['result'] as $r) {
                $score += $r;
                $tbscore += $score;
            }
        }

        $this->teams[$id]['cumulative'] = $tbscore;
        return $tbscore;
    }

    function get_buchholz($id) {
        if (isset($this->teams[$id]['buchholz']))
            return $this->teams[$id]['buchholz'];

        if (! $this->teams[$id]['opponents'])
            $tbscore = 0;
        else {
            foreach ($this->teams[$id]['opponents'] as $opp_id)
                $tbscore += $this->teams[$opp_id]['score'];
        }
        $this->teams[$id]['buchholz'] = $tbscore;
        return $tbscore;
    }

    function get_berger($id) {
        if (isset($this->teams[$id]['berger']))
            return $this->teams[$id]['berger'];

        $tbscore = 0;
        if ($this->teams[$id]['opponents']) {
            foreach ($this->teams[$id]['opponents'] as $idx => $opp_id) {
                if ($opp_id != -1) {
                    $result = $this->teams[$id]['result'][$idx];
                    $tbscore += $result * $this->teams[$opp_id]['score'];
                }
            }
        }

        $this->teams[$id]['berger'] = $tbscore;
        return $tbscore;
    }

    function cmp($a_id, $b_id, $callback) {
        $a = call_user_func($callback, $a_id);
        $b = call_user_func($callback, $b_id);
        if ($a == $b) return 0;
        else          return ($a < $b) ? 1 : -1;
    }

    function deep_cmp($a, $b, $comps = null) {
        if (! $comps) 
            $comps = $this->cmp_methods;

        foreach ($comps as $method) {
            if (! $tb) 
                $tb = $this->cmp($a['id'], $b['id'], array($this, $method));
        }
        return $tb;
    }

    function tiebreaks() {
        foreach (array_keys($this->teams) as $id)
            foreach ($this->cmp_methods as $method)
                $this->$method($id);
    }

    function iterateSRS() {
        $error = 0; $srs = array();
        foreach ($this->teams as $id => $team) {
            $n = count($team['results']);
            $srs[$id] = 0;
            foreach ($team['results'] as $r) {
                // add goal diff
                $srs[$id] += ($r['score'][0] - $r['score'][1]);
                // add +5/0/-5 for Win/Draw/Loss
                $srs[$id] += 10 * ($r['res'] - 0.5);
                // add fraction of opponent's SRS value from prior iteration
                $srs[$id] += $this->teams[$r['opp_id']]['srs'] / ($n+0.01);
            }
        }
        foreach ($srs as $id => $val) {
            $error += abs($val - $this->teams[$id]['srs']);
            $this->teams[$id]['srs'] = $val;
        }
        return $error;
    }

    function addByeTeam($id) {
        // Create a placeholder "bye" team with id=$id
        foreach ($this->teams as $id => $team)
            foreach ($team['results'] as $r)
                if ($r['opp_id'] == -1)
                    $this->teams[$id]['results'][] = array("res" => 0, "opp_id" => $id, "score" => array(0,0));
    }

    function calcSRS() {
        // Iterate
        $this->addByeTeam(0);
        $count = 0; $error = 1;
        while (($count < 1000) && ($error > 0.0001)) {
            $count++;
            $error = $this->iterateSRS();
        }

        // normalize
        foreach ($this->teams as $team)
            $sum += $team['srs'];
        foreach ($this->teams as $id => $team)
            $this->teams[$id]['srs'] -= $sum / count($this->teams);

        foreach ($this->teams as $team)
            $var += $team['srs'] * $team['srs'] / count($this->teams);
        echo "<div class='alert'>SRS Calc: total error: ".sprintf("%.5f", $error).", iterations: $count, mean: 0, StdDev: ".sprintf("%.2f",sqrt($var))."</div>\n";

        // remove the bye team
        unset($this->teams[0]);
    }

    function iterateBuchholz() {
        foreach ($this->teams as $id => $team) {
            $n = count($team['results']);
            $buch[$id] = $team['score'] - 0.5 * $n;
            // add average opponent value from prior iteration
            foreach ($team['results'] as $r) {
                $buch[$id] += $this->teams[$r['opp_id']]['iterBuchholz'] / ($n+0.01);
            }
        }
        foreach ($buch as $id => $val) {
            $error += abs($val - $this->teams[$id]['iterBuchholz']);
            $this->teams[$id]['iterBuchholz'] = $val;
        }
        return $error;
    }

    function calcIterBuchholz() {
        $this->addByeTeam(0);
        // Iterate
        $count = 0; $error = 1;
        while (($count < 1000) && ($error > 0.0001)) {
            $count++;
            $error = $this->iterateBuchholz();
        }
        // Normalize
        foreach ($this->teams as $team)
            $sum += $team['iterBuchholz'];
        foreach ($this->teams as $id => $team)
            $this->teams[$id]['iterBuchholz'] -= $sum / count($this->teams);
        unset($this->teams[0]);

        foreach($this->teams as $team)
            $var += $team['iterBuchholz'] * $team['iterBuchholz'] / count($this->teams);
        echo "<div class='alert'>Iterated Buchholz Calc, total error: ".sprintf("%.5f", $error).", iterations: $count, mean: 0, StdDev: ".sprintf("%.2f", sqrt($var))."</div>\n";
    }

    function iterMaxLikelihood() {
            $spread = array(0.01, 0.03, 0.06, 0.12, 0.25, 0.5, 0.75, 0.88, 0.94, 0.97, 0.99);
        $probs = array(); 
        foreach ($this->teams as $id => $team) {
            if (count($team['faced'])) {
                $denom = 0;
                foreach ($team['faced'] as $opp_id => $count)
                    $denom += $count  / ($this->teams[$id]['maxprob'] + $this->teams[$opp_id]['maxprob']);
                $probs[$id] = $team['adjscore']/ $denom;
            }
            else { $probs[$id] = 1 / count($this->teams); }

            /*
            // per-game $adj/$denom rather than cumulative -- is this a better way to go?
            if ($team['results']) {
                foreach ($team['results'] as $r) {
                    $adj = $spread[$r['score'][0]-$r['score'][1]+5];
                    $probs[$id] += $adj / ($this->teams[$id]['maxprob']+$this->teams[$r['opp_id']]['maxprob']);
                }
            }
            else { $probs[$id] = 1 / count($this->teams); }
            */
        }
        $delta = 0;
        foreach ($probs as $id => $val) {
            $delta += abs($val - $this->teams[$id]['maxprob']);
            $this->teams[$id]['maxprob'] = $val;
        }
        return $delta;
    }

    function calcMaxLikelihood() {
        // Initialize teams
        foreach($this->teams as $id => $team) {
            $this->teams[$id]['maxprob'] = 1;  // initialize maxprob
            $spread = array(0.01, 0.03, 0.06, 0.12, 0.25, 0.5, 0.75, 0.88, 0.94, 0.97, 0.99);
            foreach ($team['results'] as $r) {
                $this->teams[$id]['faced'][$r['opp_id']]++;
                //$this->teams[$id]['performed'][$r['opp_id']] += ($r['res'] - 0.5) * 0.95 + 0.5;
                // Win is 2/3, Loss is 1/3, Draw is 1/2; goal diff contributes the remaining third

                //$this->teams[$id]['adjscore'] += 1/3 + $r['res'] * 1/6 + ($r['score'][0]-$r['score'][1])/15;
                $this->teams[$id]['adjscore'] += $spread[$r['score'][0]-$r['score'][1]+5];
                //$this->teams[$id]['adjscore'] += ($r['res'] - 0.5) * 0.95 + 0.5;
            }
        }
        // Iterate
        $count=0; $delta=1;
        while (($count < 1000) && ($delta> 0.0001)) {
            $count++;
            $delta= $this->iterMaxLikelihood();
        }
        // Normalize
        $sum = 0;
        foreach($this->teams as $id => $team)
            $sum += $team['maxprob'];
        foreach($this->teams as $id => $team)
            $this->teams[$id]['maxprob'] = 100 * $this->teams[$id]['maxprob'] / $sum;

        $mean = 100 / count($this->teams);
        foreach($this->teams as $team)
            $var += ($team['maxprob']-$mean) * ($team['maxprob']-$mean) / count($this->teams);
        //echo "<div class='alert'>Max Likelihood Calc: total error: ".sprintf("%.5f", $delta).", iterations: $count, mean: ".sprintf("%.2f",$mean).", StdDev:".sprintf("%.2f",sqrt($var))."</div>\n";
    }
}

// groups elements of $ary with element[$idx] as key
function group_by($ary, $idx) {
  $ret = array();
  foreach ($ary as $entry)
    $ret[$entry[$idx]][] = $entry;
  return $ret;
}

// get cumulative stats for rounds up through $round
function build_stats($round) {
    ($db = connect_to_db()) || debug_error("Couldn't connect to database for tournament standings");
    $teams = sql_select_all("SELECT tblTeam.*, tblModuleTeams.* FROM tblTeam JOIN tblModuleTeams USING (team_id) WHERE module_id = ? ORDER BY team_seed, tblTeam.team_id", array($round['module_id']), $db);
    if (!$teams) 
        return false;
    $seed=1;
    foreach ($teams as $idx => $t) {
        $teams[$idx]['team_init'] = $seed++;
    }
    if ($round) {

    }
    $stats = new stats($round['module_id'], $teams);
    
    $rounds = sql_select_all("SELECT * FROM tblRound WHERE module_id = ? AND round_number <= ? ORDER BY round_number ASC", array($round['module_id'], $round['round_number']), $db);
    foreach ($rounds as $r) {
        // TODO: should SELECT WHERE tblGame.status = finished
        $games = sql_select_all("SELECT g.status, g.round_id, t.game_id, t.team_id, t.score FROM tblGame g JOIN tblGameTeams t WHERE t.game_id = g.game_id AND g.round_id = ?", array($r['round_id']), $db);
        foreach (group_by($games, 'game_id') as $match) {
            if ($match[0]['status'] == 1)
                $stats->add_result($match, $r['round_number']);
        }
    }
    $db = null;
    return $stats;
}

function standings_init_round($mid) {
    return array( "module_id" => $mid, 'round_number' => 0);
}

function get_standings_before($round) {
    $round['round_number']--;
    $st = get_standings($round);
    $round['round_number']++;
    return $st;
}

// standings through $round, ordered best to worst
function get_standings($round, $all_tiebreaks = false) {
    $stats = build_stats($round);
    if (! $stats)
        return false;
    $stats->calcMaxLikelihood();
    if ($all_tiebreaks) {
        $stats->tiebreaks();
        $stats->calcSRS();
        $stats->calcIterBuchholz();
    }
    return $stats->team_array();
}

?>
