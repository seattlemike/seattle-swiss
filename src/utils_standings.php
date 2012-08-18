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
    function __construct($tid, $teams = NULL) {
        $this->teams = array();
        if ($tid) {
            $this->mode = get_tournament_mode($tid);
            $this->tid = $tid;
        }
        if ($teams) {
            foreach ($teams as $t)
                $this->add_team($t);
        }
        $this->build_seeds();

        switch ($this->mode) {
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
                // -1 if disabled, 0 if eliminated, 1 or 2 if still in play
                'status' => $team['is_disabled'] ? -1 : 2,
                'opponents' => array(), // list of opponents faced in order
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
        $this->teams[$id]['results'][]   = array('rnum'     => $rnum, 
                                                 'res'      => $res, 
                                                 'opp_name' => $b['name'],
                                                 'opp_id'   => $b['team_id'],
                                                 'score'    => array($a['score'], $b['score']),
                                                 'status'   => $this->teams[$id]['status'],
                                                 );

        switch ($this->mode) {
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

    //TODO (IMMEDIATE?): bracket_idx for winners, loser_idx[$rnum] for losers?
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

        switch ($this->mode) {
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
}

// groups elements of $ary with element[$idx] as key
function group_by($ary, $idx) {
  $ret = array();
  foreach ($ary as $entry)
    $ret[$entry[$idx]][] = $entry;
  return $ret;
}

// from tournament_id, foreach round, foreach game, add game result to stats
function build_stats($tid, $nrounds) {
    ($db = connect_to_db()) || debug_error("Couldn't connect to database for tournament standings");
    //TODO:  handle disabled v non-disabled for elim tournaments?
    $teams = sql_select_all("SELECT * FROM tblTeam WHERE tournament_id = :tid", array(":tid" => $tid), $db);
    $stats = new stats($tid, $teams);
    
    $rounds = sql_select_all("SELECT * FROM tblRound WHERE tournament_id = :tid ORDER BY round_number ASC", array(":tid" => $tid), $db);
    foreach ($rounds as $r) {
        if (($nrounds == -1) || ($nrounds >= $r['round_number'])) {  // nrounds > -1 means partial stats computation
            // TODO: should SELECT WHERE tblGame.finished = true
            $games = sql_select_all("SELECT g.round_id, t.game_id, t.team_id, t.score FROM tblGame g JOIN tblGameTeams t WHERE t.game_id = g.game_id AND g.round_id = :rid", array(":rid" => $r['round_id']), $db);
            foreach (group_by($games, 'game_id') as $match) {
                if (min(array_map( function ($s) { return $s['score']; }, $match )) > -1)
                    $stats->add_result($match, $r['round_number']);
            }
        }
    }
    $db = null;
    return $stats;
}

// teams ordered best to worst
function get_standings($tid, $all_tiebreaks = false, $nrounds = -1) {
    $stats = build_stats($tid, $nrounds);
    if ($all_tiebreaks) $stats->tiebreaks();
    return $stats->team_array();
}

?>
