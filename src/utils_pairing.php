<?php

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

function bracket_match($teams) {

    debug_error(0, "Please write function", "bracket_match");

    if (pow(2, intval(log(count($teams), 2))) != count($teams))
        debug_error(101, "Expected power of 2 many teams", "bracket_match");

    // calculate pos vals?

    // returns an array of $nbye-many teams with ['seed'] closest to $center
    if (log(count($new), 2) == intval(log(count($new), 2)))
        $matches = consec_matching($new);
    else {
        $matches = consec_matching(array_filter($new, 
                      function ($t) use ($nbye_lose, $sum_win) 
                              { return (abs($t['seed']-($sum_win/2)) > $nbye_lose); }));
        // then add in the byes
        $byes = array_filter($new, function ($t) use ($nbye_lose, $sum_win) 
                                       { return (abs($t['seed']-($sum_win/2)) < $nbye_lose); });
        foreach ($byes as $t)
            $matches[] = array($t);
    }
}

function get_dblelim_pairings($tid) {
    // standings array of all teams not disabled
    $standings = array_filter(get_standings($tid), function ($t) { return ($t['status'] >= 0); });

    // number of teams with a first-round bye in each of winners bracket & losers bracket
    $n = pow(2, intval(log(count($standings),2)));
    $msg .= "2^intval(log_2(count)): $n\n<br>type: ".gettype($n)."\n";
    debug_error(0, $msg);

    $nbye_win  = count($standings) - pow(2, ceil(log(count($standings), 2))) - count($standings);
    $nlose = (count($standings) - $nbye_win) / 2;
    $nbye_lose = pow(2, ceil(log($nlose, 2))) - $nlose;
    $sum_win  = count($standings) + $nbye_win + 1;

    $lbracket = array_filter($standings, function ($t) { return ($t['status'] == 1); });
    $wbracket = array_filter($standings, function ($t) { return ($t['status'] == 2); });

    // LOSERS' BRACKET MATCHES
    if (count($lbracket) >= 2) {
        // sort by what index we are in the losers bracket
        array_multisort(array_map(function($t) {return $t['bracket_idx'];}, $lbracket), SORT_NUMERIC, $lbracket);
        // newly minted losers / existing losers
        $new = array_values(array_filter($lbracket, function ($t) { return (end($t['result']) == 0); }));
        $old = array_values(array_filter($lbracket, function ($t) { return (end($t['result']) > 0); }));

        // if count(old) = 2*count(new) then old v old
        // if count(old) = count(new)   then old v new
        // if count(old) = 0            then new v new [Round2, the first losers round]
        if (count($old) == 0) {  // Round2
            // for n teams with k byes, give byes to those seeded closest to (n-k)/2
            //   i.e. whoever would be the highest seeded _if games go according to seed_
            $matches = bracket_match($new);  // possible byes
        }
        elseif (count($old) == 2*count($new)) {
            $matches = consec_matching($old);
        }
        elseif (count($old) == count($new)) {
            if (log(count($new),2) % 2) { $new = array_merge(array_slice($new, count($new)/2),array_slice($new, 0, count($new)/2)); }
            $matches = array();
            foreach($old as $idx => $team)
                $matches[] = array($old[$idx], $new[$idx]);
        }
        else
            debug_error(100, "Unexpected number of old/new losers bracket teams", "get_dblelim_pairings");
        // update the pairs list
        $pairs=$matches;
    }
    else
        $pairs = array();
    
    // Finals Special!
    if ((count($lbracket) == 1) && (count($wbracket) == 1)) {
        $pairs[] = array_merge($lbracket, $wbracket);
    }

    // WINNERS' BRACKET MATCHES (scheduled for rounds 1,2,[all 2n+1])
    if (count($lbracket) <= 2*count($wbracket)) {
        if (count($wbracket) < 2) return $pairs;

        // sort winners by pos
        array_multisort(array_map(function($t) {return $t['pos'];}, $wbracket), SORT_NUMERIC, $wbracket);
        // [as above, match everyone if pow2, else figure out who has a bye and match accordingly]
        if (log(count($wbracket), 2) == intval(log(count($wbracket), 2)))
            $matches = consec_matching($wbracket);
        else {
            $matches = consec_matching(array_filter($wbracket, function ($t) use ($nbye_win) { return ($t['seed'] > $nbye_win); }));
            $byes = array_filter($wbracket, function($t) use ($nbye_win) { return ($t['seed'] <= $nbye_win); });
            foreach ($byes as $t)
                $matches[] = array($t);
        }
        $pairs = array_merge($pairs, $matches);
    }

    // add matches to the list
    return $pairs;

}

// returns pairings for single elimination
function get_sglelim_pairings($tid) {
    // undefeated teams, sorted BY GAME ORDER
    $teams = array_filter(get_standings($tid), function ($t) { return ($t['status'] > 0); });

    if (count($teams) < 2) return array();  //bail if we've got fewer than 2 teams
    array_multisort(array_map(function($t) {return $t['pos'];}, $teams), SORT_NUMERIC, $teams);

    if (log(count($teams), 2) == intval(log(count($teams), 2))) {
        return consec_matching($teams);
    } else {
        // filter out all teams ranked at least nbye, pair the rest
        $nbye = pow(2, ceil(log(count($teams), 2))) - count($teams);
        $pairs = consec_matching(array_filter($teams, function ($t) use ($nbye) { return ($t['seed'] > $nbye); }));
        $byes = array_filter($teams, function($t) use ($nbye) { return ($t['seed'] <= $nbye); });
        foreach ($byes as $t)
            $pairs[] = array($t);
        return $pairs;
    }
}

// NEW IDEA:  goal is to avoid eventual rematches in the bottom chunk of the tourney
//  create a weighted edge-graph - teams as vertices, edges for teams who have not yet met
//    higher weights for less desirable pairings
//  find a minimal-weight perfect matching
//

function get_swiss_pairings($tid) {
    // $teams:  not disabled, ordered BEST TO WORST
    $teams = array_filter(get_standings($tid), function ($t) { return ($t['status'] > 0); } );
    array_multisort(array_map(function($t) {return $t['index'];}, $teams), SORT_NUMERIC, $teams);
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
