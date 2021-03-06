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

// groups returned in order received
//   within a group ORDER IS PRESERVED 
function semi_group($teams) {
  foreach ($teams as $t) {
    if ($semi == $t['score'])
      $g[] = $t;
    else {
      if (isset($g))
        $groups[] = $g;
        //$groups[] = array_reverse($g); 
      $semi = $t['score'];
      $g = array($t);
    } 
  }
  if (isset($g))
    $groups[] = $g;
    //$groups[] = array_reverse($g);

  return $groups;
}


function have_played($a, $b) {
  if (! isset($b['opponents'])) return false;
  return in_array($a['id'], $b['opponents']);
}

function try_matching($g) {
  // go through the group from the bottom, making matches against the top
  $rem = array();
  $pairs = array();
  while (count($g) > 1) {
    $a = array_pop($g);
    reset($g);
    while (have_played($a, current($g)))
      next($g);
    if (current($g)) {
      $pairs[] = array($a, current($g));
      unset($g[key($g)]);
    } else {
      $rem[] = $a;
    }
  }
  // g could still have one last remnant
  //   and let's order the remnants in descending rank
  $rem = array_merge($g, array_reverse($rem));
  return array($rem, $pairs);
}

function outer_matching($g) {
    $pairs = array();
    while (count($g) > 1) {
        $a = array_pop($g);
        $b = array_shift($g);
        $pairs[] = array($a, $b);
    }
    return $pairs;
}

function consec_matching($g) {
    $pairs = array();
    while (count($g) > 1) {
        $a = array_shift($g);
        $b = array_shift($g);
        $pairs[] = array($b, $a);
    }
    return $pairs;
}


// ASSERT:  SWISS_MODE=0 SINGLE_ELIM_MODE=1 DOUBLE_ELIM_MODE=2 ROUND_ROBIN_MODE=3
function round_get_pairings($round) {
    $module = get_module($round['module_id']);
    if (!$module) 
        return false;
    switch ($module['module_mode']) {
        case MODULE_MODE_SWISS:
            return get_swiss_pairings($round);
        case MODULE_MODE_SGLELIM:
            return get_sglelim_pairings($round);
        case MODULE_MODE_DBLELIM:
            return get_dblelim_pairings($round);
        case MODULE_MODE_ROBIN:
            die("Oops:  Round Robin mode is not yet functional");
        default:  // oh no!  what other modes do we have?
            return array();
    }
}

//TODO MIKE IMMEDIATE
function get_dblelim_pairings($tid) {
    $standings = array_filter(get_standings($tid), function ($t) { return ($t['status'] >= 0); });
    $lbracket = array_filter($standings, function ($t) { return ($t['status'] == 1); });
    $wbracket = array_filter($standings, function ($t) { return ($t['status'] == 2); });

    /*
    foreach($standings as $k => $t) {
        debug_alert($k);
        print_r($t);
    }
    debug_alert("LOSERS");
    foreach($lbracket as $k => $t) {
        debug_alert($k);
        print_r($t);
    }
    */

    // LOSERS' BRACKET MATCHES
    if (count($lbracket) == 0)
        $pairs = array();
    elseif (count($lbracket) == 1) {
        if (count($wbracket) == 1)      // Finals Special!
            $pairs[] = array_merge($lbracket, $wbracket);
        else {
            $pairs = array($lbracket);  // AWKWARD: we have 2^k+1 many teams in the dblelim
        }
    }
    else {
        // flip bracket_idx on odd-rounds to avoid rematches
        // if (log(count($new),2) % 2) { $new = array_merge(array_slice($new, count($new)/2),array_slice($new, 0, count($new)/2)); }
        
        // newly minted losers / existing losers
        $new = array_values(array_filter($lbracket, function ($t) { $a = end($t['results']); return ($a['res'] == 0); }));
        $old = array_values(array_filter($lbracket, function ($t) { $a = end($t['results']); return ($a['res'] > 0); }));

        // if count(old) = 2*count(new) then old v old
        // if count(old) = count(new)   then old v new
        // if count(old) = 0            then new v new [Round2, the first losers round]
        // if count(old) < count(new)   then old v new with some new BYE [Round3-can-be-awkward scenario]
        if (count($old) == 0) {  // Round2
            // compute number of teams with a first-round bye in each of winners bracket & losers bracket
            $n = pow(2, (int) ceil(log(count($standings),2))-1);
            $nbye_win = 2 * $n - count($standings);
            $nlose = count($standings) - $n;
            $nbye_lose = $n - $nlose;
            $seed_center = (count($standings) + $nbye_win + 1) / 2;
            //MIKE IMMEDIATE DEBUG print statement
            //debug_alert( "n: $n, nteams: ".count($standings).", nbye_win: $nbye_win, seed_center: $seed_center, nlose: $nlose, nbye_lose: $nbye_lose");

            // for n teams with k byes, give byes to those seeded closest to (n-k)/2
            //   i.e. whoever would be the highest seeded _if games go according to seed_
            if ($nbye_lose)
                $bye_filter = function ($t) use ($nbye_lose, $seed_center) { return (abs($t['seed']-$seed_center) > $nbye_lose); };
            $matches = bracket_match($new, 'loser_idx', $bye_filter);
        }
        elseif (count($old) == 2*count($new)) {
            $matches = bracket_match($old, 'loser_idx');
        }
        elseif (count($old) == count($new)) {
            $matches = bracket_match(array_merge($old, $new), 'loser_idx');
        }
        else { // Round3 with BYES
            
            // classes size [8?]
            //$indexed[$t['loser_idx']] = $t;
            foreach (array_merge($new, $old) as $t)
                $class[(int) ($t['loser_idx']/8)]++;
            $bye_filter = function ($t) use ($class) { return ($class[(int) ($t['loser_idx']/8)] == 2); };
            $matches = bracket_match(array_merge($old, $new), 'loser_idx', $bye_filter);
        }
        // update the pairs list
        $pairs=$matches;
    }
    

    // WINNERS' BRACKET MATCHES (scheduled for rounds 1,2,[all 2n+1])
    if (count($lbracket) <= 2*count($wbracket)) {
        $nbye = pow(2, (int) ceil(log(count($wbracket), 2))) - count($wbracket);
        $bye_filter = function ($t) use ($nbye) { return ($t['seed'] > $nbye); };
        $pairs = array_merge($pairs, bracket_match($wbracket, 'bracket_idx', $bye_filter));
    }

    // add matches to the list
    return $pairs;

}

function bracket_match($teams, $sort_field, $bye_filter=null) {
    if (count($teams) < 2) return array();  // bail if we've got fewer than 2 teams

    /*
    debug_alert("Sort field: $sort_field"); 
    debug_alert("unsorted teams");
    foreach($teams as $t) {
        debug_alert($t['name'].": ".$t[$sort_field]);
        print_r($t);
    }
    */

    // sort $teams by [$sort_field]
    array_multisort(array_map(function($t) use ($sort_field) {return $t[$sort_field];}, $teams), SORT_NUMERIC, $teams);

    // determine byes using $bye_filter callback [returns true if _NOT_ a team to bye]
    if ($bye_filter) {
        $to_pair = array_filter($teams, $bye_filter);
        $to_bye = array_filter($teams, function ($t) use ($bye_filter) { return (! $bye_filter($t)); });
    }
    else {
        $to_pair = $teams;
        $to_bye = array();
    }
    /*
    MIKE IMMEDIATE DEBUG
    debug_alert("<br>To pair ".count($to_pair).":<br>");
    print_r($to_pair);
    debug_alert("<br>To bye ".count($to_bye).":<br>");
    print_r($to_bye);
    die();
    */
    
    // match pairs, encapsulate the byes, and return the two arrays merged
    $pairs = consec_matching($to_pair);
    $byes  = array_map(function ($t) { return array($t); }, $to_bye);
    return array_merge($pairs, $byes);
}

// returns pairings for single elimination
function get_sglelim_pairings($tid) {
    $teams = array_filter(get_standings($tid), function ($t) { return ($t['status'] > 1); });

    // filter out all teams ranked at least nbye, pair the rest
    $nbye = pow(2, ceil(log(count($teams), 2))) - count($teams);
    $bye_filter = function ($t) use ($nbye) { return ($t['seed'] > $nbye); };
    return bracket_match($teams, 'bracket_idx', $bye_filter);
}

// MIKE TODO:  implement?  ugh.
// NEW IDEA:  goal is to avoid eventual rematches in the bottom chunk of the tourney
//  create a weighted edge-graph - teams as vertices, edges for teams who have not yet met
//    higher weights for less desirable pairings
//  find a minimal-weight perfect matching
//

function old_get_swiss_pairings($round) {
    $teams = get_standings($round);
    array_multisort(array_map(function($t) {return $t['rank'];}, $teams), SORT_NUMERIC, $teams);

    $pairs = array();
    // TODO: deal with odd number of teams better
    // for now: BYE for lowest ranked team that has not yet had a bye
    if ((count($teams) % 2) == 1) {
        // loop through $teams WORST TO BEST, pick the first one who hasn't yet had a bye
        foreach (array_reverse($teams) as $idx => $t) {
            if (! in_array(-1, $t['opponents'])) {
                $pairs[] = array($t);
                // $idx is within reversed array, so munge for $teams[index]
                unset($teams[count($teams) - $idx - 1]);
                break;
            }
        }
    }

  // get semi-groups BEST TO WORST
  $groups = semi_group($teams);

  // find matchup if possible, else merge with next semi-group, else grumpy!
  // TODO IMMEDIATE FIX: this could leave rematches in the bottom few
  $rem = array();
  foreach ($groups as $g) {
    $g = array_merge($rem, $g);
    // if remnants from last match group, take them
    list($rem, $p) = try_matching($g);
    $pairs = array_merge($pairs, $p);
  }
  if (count($rem) > 0) {
    list($rem, $p) = try_matching($rem);
    $pairs = array_merge($pairs, $p);
  }
  if (count($rem) > 0) {
    //debug_alert("rematches coming up");
    $pairs = array_merge($pairs, outer_matching($rem));
  }
  return $pairs;
}

// new pairing method: instead of best v worst within semi-group we match in quartiles, eiths, sixteenths, etc 
//    regardless of record difference
function get_swiss_pairings($round) {
    //TODO: for rematch, search through reverse pair for someone who isn't rematch 
    //      and whose opponent isnt rematch with all remaining teams
    function get_next($a, $teams) {
        for ($i = 0; $i < count($teams); $i++)
            if (($a['id'] != $teams[$i]['id']) && (! ($a['faced'][$teams[$i]['id']]))) // not me and not rematch
                return $i;
        throw new Exception("No non-rematch found for {$a['team_name']}.");
    }

    // for pairing: sort teams by w/l record and tie-break BY SEED
    $teams = get_standings($round);
    array_multisort(array_map(function($t) {return $t['score'];}, $teams), SORT_NUMERIC, SORT_DESC, array_map(function($t) {return $t['seed'];}, $teams), SORT_NUMERIC, $teams);
    $pairs = array();
    //array_multisort(array_map(function($t) {return $t['maxprob'];}, $teams), SORT_NUMERIC, array_map(function($t) {return $t['seed'];}, $teams), SORT_NUMERIC, $teams);

    // FOR ODD NUMBERS OF TEAMS:
    //    if team has played fewer games than number of rounds, give them one extra vs the bottom
    //    TODO: how do I know I want vs bottom
    //    TODO: how will I know they've been skipped in the past and so not do that again?
    //    TODO: also how do I space out the games so neither skip nor opp are playing back-to-back?
    $waiting = array_filter($teams, function ($t) use ($round) { return (count($t['results']) < $round['round_number']-1); });
    // TODO: if we have multiple skipped teams should we pair them against eachother?
    foreach ($waiting as $t) {
        // get-next gives us the reverse index (pick first non-rematch from the end), so un-reverse for $opp:
        try { // only throws an exception when all games would be rematches
            $opp = count($teams) - 1 - get_next($t, array_reverse($teams));
        } catch ( Exception $e ) {
            // TODO: find team from end against which fewest rematches?
        }
        $pairs[] = array($t, $teams[$opp]);
        array_splice($teams, $opp, 1);  // don't get rid of $t, but do get rid of $opp
    }
    
    // Pairing Method:  in round k, pair seeds 1 vs 2^k+1, 2 vs 2^k+2, etc
    $quartile = pow(2,$round['round_number']);
    if ($round['round_number'] == 1)
        $quartile *= 2;
    $interval = ceil(count($teams) / $quartile);

    while (count($teams) > 0) {
        $waiting = array_splice($teams, 0, $interval);
        while (count($waiting) > 0 && count($teams) > 0) {
            try {
                $opp = get_next($waiting[0], $teams);
            } catch (Exception $e) {  // rematch avoidance time
                foreach ($pairs as $p) {
                    // TODO IMMEDIATE: rematch avoidance
                }
            }
            $pairs[] = array($teams[$opp], array_shift($waiting));
            array_splice($teams, $opp, 1);
        }
    }
    // LEFTOVERS in $waiting, first v last until all done
    while (count($waiting) > 1)
        $pairs[] = array(array_pop($waiting), array_shift($waiting));

    //throw new Exception("Test pairings");
    return $pairs;
}

?>
