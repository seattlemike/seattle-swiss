<?php

// groups returned in order received
//   within a group ORDER IS PRESERVED 
function semi_group($teams) {
  foreach ($teams as $t) {
    if ($semi == $t['rank'])
      $g[] = $t;
    else {
      if (isset($g))
        $groups[] = $g;
        //$groups[] = array_reverse($g); 
      $semi = $t['rank'];
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

function force_matching($g) {
  while (count($g) > 1) {
    $a = array_pop($g);
    $b = array_shift($g);
    $pairs[] = array($a, $b);
  }
  return $pairs;
}


// ASSERT:  SWISS_MODE=0 SINGLE_ELIM_MODE=1 DOUBLE_ELIM_MODE=2 ROUND_ROBIN_MODE=3
function tournament_get_pairings($tid) {
    $mode = get_tournament_mode($tid);
    if     ($mode == 0) return get_swiss_pairings($tid);
    elseif ($mode == 1) return get_single_pairings($tid);
    else // Ohno!  No pairings yet for this mode!
        return array();
}

function get_single_pairings($tid) {
    // teams not yet out, sorted best to worst
    $teams = array_filter(single_standings($tid), function ($t) { return ($t["live"]); });
    $pairs = array();
    if (log(count($teams), 2) == int(log(count($teams), 2))) {

    } else {  // we've got some Byes


    }

    return $pairs;
}

// NEW IDEA:  goal is to avoid eventual rematches in the bottom chunk of the tourney
//  create a weighted edge-graph - teams as vertices, edges for teams who have not yet met
//    higher weights for less desirable pairings
//  find a minimal-weight perfect matching
//

function get_swiss_pairings($tid) {
    // $teams:  not disabled, ordered BEST TO WORST
    $teams = array_filter(swiss_standings($tid), function ($team) { return (! $team['disabled']); } );
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
    $pairs = array_merge($pairs, force_matching($rem));
  }
  return $pairs;
}


?>
