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
function tournament_get_pairings($tid) {
    switch(get_tournament_mode($tid)) {
        case 0:
            return get_swiss_pairings($tid);
        case 1:
            return get_sglelim_pairings($tid);
        case 2:
            return get_dblelim_pairings($tid);
        default:  // oh no!  what other modes do we have?
            return array();
    }
}

//TODO MIKE IMMEDIATE
function get_dblelim_pairings($tid) {
    $standings = array_filter(get_standings($tid), function ($t) { return ($t['status'] >= 0); });

    $lbracket = array_filter($standings, function ($t) { return ($t['status'] == 1); });
    $wbracket = array_filter($standings, function ($t) { return ($t['status'] == 2); });

    // LOSERS' BRACKET MATCHES
    if (count($lbracket) < 2)
        $pairs = array();
    else {
        // flip bracket_idx on odd-rounds to avoid rematches
        // if (log(count($new),2) % 2) { $new = array_merge(array_slice($new, count($new)/2),array_slice($new, 0, count($new)/2)); }
        
        // newly minted losers / existing losers
        $new = array_values(array_filter($lbracket, function ($t) { $a = end($t['results']); return ($a['res'] == 0); }));
        $old = array_values(array_filter($lbracket, function ($t) { $a = end($t['results']); return ($a['res'] > 0); }));

        // if count(old) = 2*count(new) then old v old
        // if count(old) = count(new)   then old v new
        // if count(old) = 0            then new v new [Round2, the first losers round]
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
        else {
            debug_alert("LIKELY: round 3 has byes in losers bracket.  FIX");
            debug_error(100, "Unexpected number of old/new losers bracket teams", "get_dblelim_pairings");
        }
        // update the pairs list
        $pairs=$matches;
    }
    
    // Finals Special!
    if ((count($lbracket) == 1) && (count($wbracket) == 1)) {
        $pairs[] = array_merge($lbracket, $wbracket);
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

    // determine byes using $bye_filter callback
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

// MIKE TODO IMMEDIATE:  implement?  ugh.
// NEW IDEA:  goal is to avoid eventual rematches in the bottom chunk of the tourney
//  create a weighted edge-graph - teams as vertices, edges for teams who have not yet met
//    higher weights for less desirable pairings
//  find a minimal-weight perfect matching
//

function get_swiss_pairings($tid) {
    // $teams:  not disabled, ordered BEST TO WORST
    $teams = array_filter(get_standings($tid), function ($t) { return ($t['status'] > 0); } );
    array_multisort(array_map(function($t) {return $t['rank'];}, $teams), SORT_NUMERIC, $teams);

    $pairs = array();

    // TODO: instead of BYE... something?
    // I hate odd numbers: BYE for lowest ranked team that has not yet had a bye
    if ((count($teams) % 2) == 1) {
        // loop through $teams WORST TO BEST
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
  // TODO this could leave rematches in the bottom few
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
    debug_alert("rematches coming up");
    $pairs = array_merge($pairs, outer_matching($rem));
  }
  return $pairs;
}


?>
