<?php

function make_rand ($seed ) {
srand($seed);
return rand();
}

class stats {
  public $teams;
  function __construct() {
    $this->teams = array();
  }

  function add_team($team) {
    // really should just take id [and name?]
    $this->teams[$team['team_id']] = array('name' => $team['team_name'],
                                           'id'   => $team['team_id'],
                                           'text' => $team['team_text'],
                                           'disabled' => $team['is_disabled'],
                                           'rand' => make_rand($team['team_id']),
                                           'opponents' => array(),
                                           'result' => array(),
                                           );
  }

  function add_result($game) {
    if (count($game) == 1)
      $this->add_team_result($game[0]['team_id'], 1, -1);
    else {
      if ($game[0]['score'] < $game[1]['score'])      $res = array(0,1);
      else if ($game[0]['score'] > $game[1]['score']) $res = array(1,0);
      else                                            $res = array(0.5,0.5);

      foreach (array(0,1) as $idx)
        $this->add_team_result($game[$idx]['team_id'], $res[$idx], $game[($idx+1)%2]['team_id']);
    }
  }

  function add_team_result($my_id, $res, $opp_id) {
    if ($opp_id == -1) $opp_name = "BYE";
    else               $opp_name = $this->teams[$opp_id]['name'];
    //echo "add team result $my_id, $res, $opp_id<br>";
    $this->teams[$my_id]['opponents'][] = $opp_id;
    $this->teams[$my_id]['opp_name'][] = $opp_name;
    $this->teams[$my_id]['result'][] = $res;
    $this->teams[$my_id]['score'] += $res;
  }

  function team_array() {
    //assign partial rankings, compute par
    $standings = $this->teams;
    usort($standings, array($this, 'team_cmp'));
    // naive ranking
    foreach ($standings as $idx => $t)
      $this->teams[$t['id']]['rank'] = $idx + 1;
    // partial ranking
    foreach ($standings as $t) {
      $t = $this->teams[$t['id']];
      if (isset($prev) && 
          $prev['score'] == $t['score'] &&
          $prev['buchholz'] == $t['buchholz'] &&
          $prev['berger'] == $t['berger'] &&
          $prev['cumulative'] == $t['cumulative']
         )
        $this->teams[$t['id']]['rank'] = $prev['rank'];

      $prev = $this->teams[$t['id']];
    }

    $standings = $this->teams;
    usort($standings, array($this, 'team_cmp'));
    foreach($standings as $idx => $t)
      $standings[$idx]['par'] = $this->teams[$t['id']]['par'];

    //assign partial ranks on score
    unset($prev);
    foreach($standings as $idx => $t) {
      if (isset($prev) && ($prev['score'] == $t['score']))
        $standings[$idx]['rank'] = $prev['rank'];
      else $standings[$idx]['rank'] = $idx+1;
      $prev = $standings[$idx];
    }
    return $standings;
  }

    function get_score($id) {
        return $this->teams[$id]['score'];
    }

    function get_rand($id) {
        return $this->teams[$id]['rand'];
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

        if (! $this->teams[$id]['opponents'])
            $tbscore = 0;
        else {
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

    function team_cmp($a, $b) {
        $comps = array('get_score', 'get_buchholz', 'get_berger', 'get_cumulative', 'get_rand');
        foreach ($comps as $method) {
            if (! $tb) 
                $tb = $this->cmp($a['id'], $b['id'], array($this, $method));
        }
        return $tb;
    }
}

// returns teams array indexed by team_id
function order_by($ary, $idx) {
  $ret = array();
  foreach ($ary as $entry)
    $ret[$entry[$idx]] = $entry;
  return $ret;
}

function group_by($ary, $idx) {
  $ret = array();
  foreach ($ary as $entry)
    $ret[$entry[$idx]][] = $entry;
  return $ret;
}

// teams ordered best to worst
function swiss_standings($tid) {
    $stats = new stats();
    ($db = connect_to_db()) || die("Couldn't connect to database for tournament standings");

    $team_query = "SELECT * FROM tblTeam WHERE tournament_id = :tid";
    foreach (sql_select_all($team_query, array(":tid" => $tid), $db) as $team)
        $stats->add_team($team);
  
    foreach (sql_select_all("SELECT * FROM tblRound WHERE tournament_id = :tid ORDER BY round_number ASC", array(":tid" => $tid), $db)
             as $round) {
        $games = group_by(sql_select_all("SELECT * FROM tblGame JOIN tblGameTeams USING (game_id) WHERE tblGame.round_id = :rid", array(":rid" => $round['round_id']), $db),
                      'game_id');
        foreach ($games as $match)
            if ((count($match) == 1) || min($match[0]['score'], $match[1]['score']) >= 0)
                $stats->add_result($match);
    }
  
    $db = null;
    return $stats->team_array();
}


function single_standings($tid) {
    return array();
}

?>
