<?php

require_once('utils_db.php');
require_once('utils_display.php');

function max_session_life() {
    return 2700;  // 45 minute session timeout
}

//
// Credentials functions
//

function require_privs($stmt, $warning='') {
    if ($_SESSION['admin_type'] == 'super')
        return true;
    if (!$warning)
        $warning = 'Improper priveliges to perform this action';
    if (!$stmt)
        die($warning);
}

function check_login() {
    return isset($_SESSION['admin_id']);
}

function require_login() {
    if (!check_login()) {
        header('location:admin.php');
        die();
    }
}

// returns credentials row or FALSE if no matches
function get_credentials($user, $pass) {
    return sql_select_one('SELECT * FROM tblAdmin WHERE admin_email = :name and admin_pass = :pass',
            array(':name' => $user, ':pass' => salt_pass($pass)));
}

function login($user, $pass) {
    if ($creds = get_credentials($user, $pass)) {
        //TODO: should make sure that a second session_start isn't going to ever
        //      be an issue
        session_start();
        $_SESSION['admin_id'] = $creds['admin_id'];
        $_SESSION['admin_type'] = $creds['admin_type'];
        $_SESSION['admin_name'] = $creds['admin_name'];
        return true;
    }
    return false;
}

function logout() {
    // destroy session cookie
    setcookie(ini_get('session.name'),'',1,'/');
    session_destroy();
    header("location:index.php");
}

function tournament_isadmin($tid, $aid) {
    if ($_SESSION['admin_type'] == 'super')
        return true;
    $fetch = sql_select_one("SELECT * FROM tblTournamentAdmins
      WHERE tournament_id = ? AND admin_id = ?", array($tid, $aid));
    return ($fetch != false);
}

function tournament_isowner($tid, $aid) {
    if ($_SESSION['admin_type'] == 'super')
        return true;
    $t = get_tournament($tid, $aid);
    return ($t && $t['tournament_owner'] == $aid);
}

function tournament_ispublic($tid) {
    return is_array(sql_select_one('SELECT * FROM tblTournament WHERE tournament_id = :tid AND is_public = 1', array(':tid' => $tid)));
}

//
// Admin functions
//
// returns (bool STATUS, str DETAILS)
function create_admin($db, $email, $name, $city, $pass) {
    $count = sql_select_one('SELECT COUNT(*) FROM tblAdmin WHERE admin_email = :email', array(':email' => $email));
    if (!$count)
        return array(false, "Database access failed on create_admin attempt");
    elseif ($count[0] > 0)
        return array(false, "Admin account with that email already exists");
    // TODO: validate input data
    $success = sql_try('INSERT INTO tblAdmin (admin_name, admin_city, admin_pass, admin_type, admin_email) values (?,?,?,?,?)',
                    array($name, $city, salt_pass($pass), "tournament", $email));
    return array($success, "New admin INSERT");
}

//
// Tournament Functions:
//

function get_tournament_name($tid) {
  $tourney = sql_select_one('SELECT * FROM tblTournament WHERE tournament_id = :tid', array(':tid' => $tid));
  return $tourney['tournament_name'];
}

function get_tournament($tid, $aid) {
    if (tournament_isadmin($tid, $aid))
        return sql_select_one('SELECT * FROM tblTournament WHERE tournament_id = :tid', array(':tid' => $tid));
    else
        return false;
}

function get_my_tournaments($aid) {
    return sql_select_all("SELECT * FROM tblTournamentAdmins JOIN tblTournament USING (tournament_id)
         WHERE tblTournamentAdmins.admin_id = :admin_id
         ORDER BY tblTournament.tournament_date DESC",
            array(':admin_id' => $aid));
}

function get_tournament_admins($tid) {
    return sql_select_all("SELECT * FROM tblTournamentAdmins JOIN tblAdmin USING (admin_id)
         WHERE tblTournamentAdmins.tournament_id = :tid",
            array(':tid' => $tid));
}

function get_tournament_teams($tid) {
    return sql_select_all("SELECT * FROM tblTeam WHERE tournament_id = :tid",
            array(':tid' => $tid));
}

function get_tournament_nrounds($tid) {
    $round = sql_select_one("SELECT MAX(round_number) as rnum FROM tblRound WHERE tournament_id = :tid", array(":tid" => $tid));
    if (! $round['rnum'])
        return 0;
    return $round['rnum'];
}

function get_tournament_status($tid) {
    $db = connect_to_db();
    $r = get_current_round($tid, $db);
    if (! $r) return array(0,0,0);
    $games = sql_select_all("SELECT * FROM tblGame WHERE round_id = :rid", array(":rid" => $r['round_id']), $db);
    $scores = sql_select_all("SELECT MIN(tblGameTeams.score) > -1 as min FROM tblGame JOIN tblGameTeams USING (game_id) WHERE tblGame.round_id = :rid GROUP BY tblGame.game_id", 
                              array(":rid" => $r['round_id']), $db);
    $played = array_filter( $scores, function ($q) { return $q['min']; } );
    return array($r['round_number'], count($played), count($games));
}

function get_tournament_round($tid, $rid, $db=null) {
    return sql_select_one("SELECT * FROM tblRound WHERE tournament_id = :tid AND round_id = :rid", 
                          array(":tid" => $tid, ":rid" => $rid), $db);
}

// return the round_id of the round with the highest round_number [for tournament_id $tid]
function get_current_round($tid, $db=null) {
    return sql_select_one("SELECT * FROM tblRound WHERE tournament_id = :tid ORDER BY round_number DESC", 
                          array(":tid" => $tid), $db);
}

function tournament_add_admin($data, $aid) {
    require_privs(tournament_isowner($data['tournament_id'], $aid));
    $admin = sql_select_one("SELECT * FROM tblAdmin WHERE admin_email = ?", array($data['admin_email']));
    if (!$admin)
        return false;
    $already = sql_select_one("SELECT * FROM tblTournamentAdmins WHERE tournament_id = ? AND admin_id = ?",
                    array($data['tournament_id'], $admin['admin_id']));
    if ($already)
        return false;
    return sql_try("INSERT INTO tblTournamentAdmins (tournament_id, admin_id) VALUES (?, ?)",
            array($data['tournament_id'], $admin['admin_id']));
}

function tournament_remove_admin($data, $aid) {
    require_privs(tournament_isowner($data['tournament_id'], $aid));
    if ($data['admin'] == $aid)
        return false;
    $admin = sql_select_one("SELECT * FROM tblTournamentAdmins WHERE tournament_id = ? AND admin_id = ?", array($data['tournament_id'], $data['admin_id']));
    if (!$admin)
        return false;
    return sql_try("DELETE FROM tblTournamentAdmins WHERE tournament_id = ? AND admin_id = ?", array($data['tournament_id'], $data['admin_id']));
}

function tournament_create($data, $aid) {
    $bind_vars = array(':tname' => $data['tournament_name'],
        ':tcity' => $data['tournament_city'],
        ':tdate' => date("Y-m-d", strtotime($data['tournament_date'])),
        ':tpub' => isset($data['is_public']),
        ':aid' => $aid);
    $newid = sql_insert("INSERT INTO tblTournament
    (tournament_name, tournament_city, tournament_date, tournament_owner, is_public) 
    VALUES (:tname, :tcity, :tdate, :aid, :tpub)", $bind_vars);
    if ($newid != false) {
        $success = sql_try("INSERT INTO tblTournamentAdmins
        (tournament_id, admin_id) VALUES (?, ?)", array($newid, $aid));
    }
    else
        $success = false;
    return $success;
}

function tournament_edit($data, $aid) {
    require_privs(tournament_isadmin($data['tournament_id'], $aid));  //already done?
    $bind_vars = array(':tname' => $data['tournament_name'],
        ':tcity' => $data['tournament_city'],
        ':tdate' => date("Y-m-d", strtotime($data['tournament_date'])),
        ':tpub' => isset($data['is_public']),
        ':tover' => isset($data['is_over']),
        ':tid' => $data['tournament_id'] );
    $success = sql_try("UPDATE tblTournament SET tournament_name = :tname, tournament_city = :tcity,
    tournament_date = :tdate, is_public = :tpub, is_over = :tover WHERE tournament_id = :tid", $bind_vars);
    return $success;
}

function tournament_delete($data, $aid) {
  $db = connect_to_db();
  $tid = $data['tournament_id'];
  require_privs(tournament_isowner($tid,$aid));
  $success = sql_try("DELETE FROM tblTournament WHERE tournament_id = ?", array($tid), $db);
  $success &= sql_try("DELETE FROM tblTournamentAdmins WHERE tournament_id = ?", array($tid), $db);

  // not sure how to measure success here - we don't always have Teams, Rounds, Games, Scores
  sql_try("DELETE FROM tblTeam WHERE tournament_id = ?", array($tid), $db);
  $rounds = sql_select_all("SELECT * FROM tblRound WHERE tournament_id = :tid", array(":tid" => $tid), $db);
  if ($rounds) {
    $success &= sql_try("DELETE FROM tblRound WHERE tournament_id = :tid", array(":tid" => $tid), $db);
    foreach ($rounds as $r) {
      $games = sql_select_all("SELECT * FROM tblGame WHERE round_id = :rid", array(":rid" => $r['round_id']), $db);
      if ($games) {
        $success &= sql_try("DELETE FROM tblGame WHERE round_id = :rid", array(":rid" => $r['round_id']), $db);
        foreach ($games as $g) {
          $success &= sql_try("DELETE FROM tblGameTeams WHERE game_id = :gid", array(":gid" => $g['game_id']), $db);
        }
      }
    }
  }
  return $success;
}

function tournament_next_round($rid) {
  $round = sql_select_one("SELECT * FROM tblRound WHERE round_id = :rid", array(":rid" => $rid));
  if (! $round) return false;
  return sql_select_one("SELECT * FROM tblRound WHERE tournament_id = :tid AND round_number = :rnum", 
      array(":tid" => $round['tournament_id'], ":rnum" => intval($round['round_number'])+1));
}

function tournament_round_is_empty($rid) {
  $games = sql_select_one("SELECT * FROM tblGame WHERE round_id = :rid", array(":rid" => $rid));
  return (! $games);
}

function tournament_round_is_done($rid) {
  // TODO should check that $rid corresponds to an existing round?
  if (tournament_round_is_empty($rid))
    return false;
  else {
    $round = sql_select_one("SELECT MIN(tblGameTeams.score) as min_score FROM tblGame JOIN tblGameTeams USING (game_id) WHERE tblGame.round_id = :rid", array(":rid" => $rid));
    return ($round['min_score'] >= 0);
  }
}

function tournament_has_rounds($tid) {
    $select = sql_select_one("SELECT * FROM tblRound WHERE tournament_id = :tid", array(":tid" => $tid));
    return ($select != false);
}

function tournament_is_over($tid) {
    $select = sql_select_one("SELECT * FROM tblTournament WHERE tournament_id = :tid", array(":tid" => $tid));
    return ($select && $select['is_over']);
}

function tournament_add_round($tid) {
    $select = sql_select_one("SELECT MAX(round_number) AS round_max FROM tblRound WHERE tournament_id = :tid", array(":tid" => $tid));
    if (!$select) $rnum = 1;
    else          $rnum = $select["round_max"] + 1;
    return sql_insert("INSERT INTO tblRound (round_number, tournament_id) VALUES (:rnum, :tid)", array(":rnum" => $rnum, ":tid" => $tid));
}


function tournament_insert_score($gid, $tid, $score=-1, $db=null) {
  return sql_insert("INSERT INTO tblGameTeams (game_id, team_id, score) VALUES (?, ?, ?)", 
                    array($gid, $tid, $score), $db);
}

function tournament_add_game($rid, $list, $db=null) {
  foreach ($list as $idx => $team) {
    if ($team['id'] == -1)
      unset($list[$idx]);
  }
  if (count($list) == 0) return;
  
  ($db) || ($db = connect_to_db());  // make sure we've got a connection

  $gid = sql_insert("INSERT INTO tblGame (round_id) VALUES (:rid)", array(":rid" => $rid), $db);
  $gid || die("failed game insert while populating round");

  if (count($list) == 1)  $score = 0;
  else                    $score = -1;
  foreach ($list as $team)
    tournament_insert_score($gid, $team['id'], $score, $db) || die("failed to insert score during add game");
}

function tournament_delete_round($rid, $aid) {
  if (!$db) $db = connect_to_db();
  // die() if don't have privs
  $success = tournament_empty_round($rid, $aid, $db);
  $success &= sql_try("DELETE FROM tblRound WHERE round_id = :rid", array(":rid" => $rid), $db);
  return $success;
}

function tournament_empty_round($rid, $aid, $db = null) {
  if (!$db) $db = connect_to_db();
  $round = sql_select_one("SELECT * FROM tblRound WHERE round_id = :rid", array(":rid"=>$rid), $db);
  if (! round) return false;
  require_privs(tournament_isadmin($round['tournament_id'], $aid));

  $games = sql_select_all("SELECT * FROM tblGame WHERE round_id = :rid", array(":rid" => $rid), $db);
  if ($games) {
    $success = sql_try("DELETE FROM tblGame WHERE round_id = :rid", array(":rid" => $rid), $db);
    foreach ($games as $g) {
      $success &= sql_try("DELETE FROM tblGameTeams WHERE game_id = :gid", array(":gid" => $g['game_id']), $db);
    }
  }
  return $success;
}

function tournament_populate_round($tid, $rid, $aid) {
  //TODO: tournament structure!
  $db = connect_to_db();

  // if we're not passed an $rid, make a new round and populate
  if (!$rid)
    ($rid = tournament_add_round($tid)) || die("<h1>Failed to add tournament round to populate</h1>");

  $round = sql_select_one("SELECT * FROM tblRound WHERE round_id = :rid AND tournament_id = :tid", array(":rid"=>$rid, ":tid"=>$tid), $db);
  if ($round) {
    require_privs(tournament_isadmin($tid, $aid));
    $pairs = tournament_get_pairings($tid);
    foreach ($pairs as $p)
      tournament_add_game($rid, $p, $db);
  }

  return $rid;
}


//TODO: shouldn't fail silently on !$data['add_name']
function team_add($data) {
    $bind_vars = array(':tname' => $data['name_add'], 
                       ':tuid'  => $data['uid_add'], 
                       ':tinit' => $data['init_add'], 
                       ':ttext' => $data['text_add'], 
                       ':toid'  => $data['tournament_id']);
    $query = "INSERT INTO tblTeam (team_name, tournament_id, team_text, team_uid, team_init) 
              VALUES (:tname, :toid, :ttext, :tuid, :tinit)";
    if (!$data['name_add']) return false;
    else                    return sql_try($query, $bind_vars);
}

//ASSERT require_privs($tid,$aid)
function team_disable($data) {
    $query = "UPDATE tblTeam SET is_disabled = 1 XOR is_disabled 
              WHERE team_id = :teamid AND tournament_id = :tid";
    $bind_vars = array(':teamid' => $data['team_id'], 
                       ':tid'    => $data['tournament_id']);
    return sql_try($query, $bind_vars);
}

//ASSERT require_privs($tid,$aid)
//ASSERT $data["tournament_id"] isn't derived from $_POST data
function team_update($data) {
    $tid = $data['team_id'];
    $bind_vars = array(':tid'   => $data["tournament_id"],
                       ':tname' => $data["name_$tid"],
                       ':tuid'  => $data["uid_$tid"],
                       ':tinit' => $data["init_$tid"],
                       ':ttext' => $data["text_$tid"]);
    $success = sql_try("UPDATE tblTeam SET team_name = :tname, team_text = :ttext, team_uid = :tuid,
                        team_init = :tinit WHERE team_id = :tid", $bind_vars);
    return $success;
}

function tournament_update_score( $gid, $data) {
  // grab tournament_id corresponding to $gid, then require_privs
  $teams = sql_select_all("SELECT * FROM tblGameTeams WHERE game_id = :gid", array(":gid" => $gid));
  foreach ($teams as $team) {
    $tid = $team['team_id'];
    $score = $data["score_$tid"];
    if ($score == "") { $score = -1; }
    else          { $score = intval($score); }
    sql_try("UPDATE tblGameTeams SET score = :score WHERE game_id = :gid AND team_id = :tid", 
            array(":score" => $score, ":gid" => $gid, ":tid" => $tid));
  }
}

//ASSERT require_privs($tid,$aid)
function tournament_delete_game( $gid ) {
  $ret = sql_try("DELETE FROM tblGame WHERE game_id = :gid", array(":gid" => $gid));
  return $ret && sql_try("DELETE FROM tblGameTeams WHERE game_id = :gid", array(":gid" => $gid));
}

//ASSERT require_privs($tid,$aid)
function tournament_toggle_court( $gid ) {
  $game = sql_select_one("SELECT * from tblGame WHERE game_id = :gid", array(":gid" => $gid));
  if ($game) {
    return sql_try("UPDATE tblGame SET court = :court WHERE game_id = :gid", array(":court" => (intval($game['court']) xor 1), ":gid" => $gid));
  }
}

function game_is_rematch($teams) {
  if (count($teams) < 2)  return false;  // or should be != 2
  $opponents = sql_select_all("SELECT b.team_id from tblGameTeams a JOIN tblGameTeams b USING (game_id) WHERE a.team_id = :tid AND a.score != -1", array(":tid" => $teams[0]['team_id']));
  foreach ($opponents as $opp)  // should check against not just teams[1], but teams[2, etc]
    if ($opp['team_id'] == $teams[1]['team_id'])
        return true;
  return false;
}

?>
