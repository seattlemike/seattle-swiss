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


require_once('utils_db.php');
require_once('utils_display.php');

function max_session_life() {
    return 2700;  // 45 minute session timeout
}

//
// Credentials functions
//

// require that the user has privileges to the tournament
function require_privs($stmt, $warning='') {
    if ($_SESSION['admin_type'] == 'super')
        return true;
    if (!$warning)
        $warning = 'Improper priveliges to perform this action';
    if (!$stmt)
        die($warning);
}

// checks to see if an admin is set
function check_login() {
    return isset($_SESSION['admin_id']);
}

function require_login() {
    if (!check_login()) {
        header('location:admin.php');
        die();
    }
}

// generate hash and email for password reset / initial email confirmation
function email_confirm ( $aid ) {
    // TODO: ResetInterval / MaxnReset from utils_settings.php?
    $MaxnReset = 2;      // at most [2] password resets allowed
    $ResetInterval = 6;  // in any [6] hour window

    $admin = sql_select_one('SELECT * FROM tblAdmin WHERE admin_id = :aid', array(':aid' => $admin_id));
    $preset = sql_select_one('SELECT COUNT(*) FROM tblPassReset WHERE admin_id = :aid AND request_time > NOW() - :interval', 
                             array(':aid' => $admin_id, ':interval' => $ResetInterval * 3600));
    if ($preset[0] >= $MaxnReset)
        debug_error(900, "At most $MaxnReset password reset attempt(s) allowed in any $ResetInterval hour span");
    else {
        $confirmation = md5($admin['admin_pass']);
        $success = sql_try('INSERT INTO tblPassReset(admin_name, admin_city, admin_pass, admin_type, admin_email) values (?,?,?,?,?)',
                       array(htmlspecialchars($data['name']), htmlspecialchars($data['location']), 
                             salt_pass($data['password']), "tournament", $data['email']));
        
    }
    
    // check timestamp of last request to change password - max [2?] request in [6?] hrs
    // generate an unpredictable string (maybe md5 of last password hash) and add to [tblPassReset? tblAdmin?]
    // new page checks confirm=[hash] and gives a new password entry box [or two, for confirm]
    // [maybe new page will be the settings page, and the hash will let the user bypass the pass-check]
}

// returns credentials row or FALSE if no matches
function get_credentials($user, $pass) {
    return sql_select_one('SELECT * FROM tblAdmin WHERE admin_email = :name and admin_pass = :pass',
            array(':name' => strtolower($user), ':pass' => salt_pass($pass)));
}

function login($user, $pass) {
    if ($creds = get_credentials($user, $pass)) {
        //TODO: should check session_start documentation to make sure that a (potential)
        //      second call to the function without session_end isn't going to ever be an issue
        session_start();
        foreach ($creds as $k => $v)
            $_SESSION[$k] = $v;
        return true;
    }
    return false;
}

function logout() {
    // destroy session cookie
    setcookie(ini_get('session.name'),'',1,'/');
    session_destroy();
    header("location:index.php");
    ob_end_flush();
    die();
}

// checks to see if the admin logged in has privileges for the tournament
function tournament_isadmin($tid, $aid) {
    return (($_SESSION['admin_type'] == 'super') ||
            is_array( sql_select_one("SELECT * FROM tblTournamentAdmins WHERE tournament_id = ? AND admin_id = ?", 
                      array($tid, $aid))));
}

function tournament_isowner($tid, $aid) {
    return (($_SESSION['admin_type'] == 'super') ||
            is_array(sql_select_one('SELECT * FROM tblTournament WHERE tournament_id = ? AND tournament_owner = ?', 
                     array($tid, $aid))));
}

function tournament_ispublic($tid) {
    return is_array(sql_select_one('SELECT * FROM tblTournament WHERE tournament_id = :tid AND is_public = 1', 
                    array($tid)));
}

function tournament_isparent($tid, $mid) {
    return is_array(sql_select_one("SELECT * FROM tblModule WHERE module_id = ? AND parent_id = ?", 
                    array($tid, $mid)));
}

function module_getteam($mid, $team_id) {
    return sql_select_one("SELECT * FROM tblModuleTeams WHERE module_id = ? AND team_id = ?", array($mid, $team_id));
}

function module_hasteam($mid, $team_id) {
    return is_array(module_getteam($mid, $team_id));
}

function tournament_hasteam($tid, $team_id) {
    return is_array(sql_select_one("SELECT * FROM tblTeam WHERE tournament_id = ? AND team_id = ?",
                    array($tid, $team_id)));
}

//
// Admin functions
//
// returns (bool STATUS, str DETAILS)
function admin_create($data) {
    // TODO: validate data? 
    //       should add admin to tblPending and move to tblAdmin on email confirmation
    //       (email doesn't get htmlspecialchars - should we watch for injection?)
    $count = sql_select_one('SELECT COUNT(*) FROM tblAdmin WHERE admin_email = :email', array(':email' => $data['email']));
    if (!$count)
        return array(false, "Database access failed on Admin Creation attempt");
    elseif ($count[0] > 0)
        return array(false, "Admin account with that email already exists");
    $success = sql_try('INSERT INTO tblAdmin (admin_name, admin_city, admin_pass, admin_type, admin_email) values (?,?,?,?,?)',
                       array(htmlspecialchars($data['name']), htmlspecialchars($data['location']), 
                             salt_pass($data['password']), "tournament", $data['email']));
    return array($success, "New admin entry added to database");
}

function is_poweruser() {
    return $_SESSION['admin_controls'];
}

//
// Module Functions:
//

function module_delete($mid) {
    return sql_try('DELETE FROM tblModuleTeams WHERE module_id = ?', array($mid)) &&
           sql_try('DELETE FROM tblModule WHERE module_id = ?', array($mid));
}

function get_module_parent($mid) {
    $m = sql_select_one('SELECT * FROM tblModule WHERE module_id = ?', array($mid));
    if (is_array($m))
        return $m['parent_id'];
    else 
        return false;
}

function get_module($mid) {
    if ($mid)
        return sql_select_one('SELECT * FROM tblModule WHERE module_id = ?', array($mid));
    else
        return false;
}

function get_module_teams($mid) {
    return sql_select_all("SELECT a.*,b.team_name FROM tblModuleTeams a JOIN tblTeam b USING (team_id) WHERE a.module_id = ? ORDER BY team_seed, team_id", array($mid));
}


function get_tournament_modules($tid) {
    return sql_select_all("SELECT * FROM tblModule WHERE parent_id = ?", array($tid));
}

function module_update_seeds($data) {
    $success = true;
    $mid = $data['module_id'];
    foreach($data as $key => $val) {
        $exp = explode("seed-",$key);
        $team_id = $exp[1];
        if (($team_id) && module_hasteam($mid, $team_id)) {
            $success &= sql_try("UPDATE tblModuleTeams SET team_seed = ? WHERE module_id = ? AND team_id = ?", 
                                array($val, $mid, $team_id));
        }
    }
    return $success;
}

// ASSERT (mid is valid and admin_id can edit them)
function module_update($data) {
    $module = get_module($data['module_id']);
    $bind_vars = array(':title' => htmlspecialchars($data['module_title']),
                       ':date' => date("Y-m-d", strtotime($data['module_date'])),
                       ':notes' => htmlspecialchars($data['module_notes']),
                       ':mode' => $module['module_mode'], // unchanged
                       ':mid' => $data['module_id']);

    // only set 'module_mode' when no rounds have yet been scheduled/played
    if (! is_array(sql_select_one("SELECT * from tblRound WHERE module_id = ?", array($mid))))
        $bind_vars[':mode'] = $data['module_mode'];

    $success = sql_try("UPDATE tblModule SET module_title = :title, module_date = :date, 
                        module_notes = :notes, module_mode = :mode WHERE module_id = :mid", $bind_vars);
}

//
// Tournament Functions:
//

function get_tournament_name($tid) {
    $t = sql_select_one('SELECT * FROM tblTournament WHERE tournament_id = :tid', array(':tid' => $tid));
    return $t['tournament_name'];
}

// return tournament mode (0 = swiss, 1 = single, 2 = double, etc)
function get_tournament_mode($tid) {
    $t = sql_select_one('SELECT * FROM tblTournament WHERE tournament_id = :tid', array(':tid' => $tid));
    return $t['tournament_mode'];
}

function get_tournament($tid) {
    return sql_select_one('SELECT * FROM tblTournament WHERE tournament_id = ?', array($tid));
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

function get_tournament_teams($tid, $sort="") {
    $q = "SELECT * FROM tblTeam WHERE tournament_id = :tid";
    if ($sort) $q = "$q ORDER BY $sort";
    return sql_select_all($q, array(':tid' => $tid));
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

// return the round record with the specified rid
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

function tournament_new_module($data, $aid) {
    require_privs(tournament_isadmin($data['tournament_id'], $aid));
    $tourney = get_tournament($data['tournament_id']);
    return sql_try("INSERT INTO tblModule (module_title, module_date, parent_id) VALUES (?, ?, ?)",
                   array("New Round", $tourney['tournament_date'], $data['tournament_id']));
}

function new_tournament($aid) {
    $newid = sql_insert("INSERT INTO tblTournament 
                (tournament_name, tournament_date, tournament_owner) VALUES (?, ?, ?)", 
                array("New Tournament", date("Y-m-d"), $aid));
    if ($newid) {
        $success = sql_try("INSERT INTO tblTournamentAdmins (tournament_id, admin_id) VALUES (?, ?)", array($newid, $aid));
        if (! $success) // TODO: created orphan tournament!  should remove! (shouldn't ever happen though)
            return false;
    }
    return $newid;
}

function tournament_create($data, $aid) {
    $bind_vars = array(':tname' => htmlspecialchars($data['tournament_name']),
        ':tdate' => date("Y-m-d", strtotime($data['tournament_date'])),
        ':aid' => $aid);
    if (in_array($data['tournament_mode'], range(0,2)))
        $bind_vars[':tmode'] = $data['tournament_mode'];

    $newid = sql_insert("INSERT INTO tblTournament
        (tournament_name, tournament_date, tournament_mode, tournament_owner) 
        VALUES (:tname, :tdate, :tmode, :aid)", $bind_vars);
    if ($newid != false) {
        $success = sql_try("INSERT INTO tblTournamentAdmins (tournament_id, admin_id) VALUES (?, ?)", array($newid, $aid));
    }
    else
        $success = false;
    return $success;
}

function tournament_update($data, $aid) {
    require_privs(tournament_isadmin($data['tournament_id'], $aid));  //already done?
    $bind_vars = array(':tname' => htmlspecialchars($data['t_name']),
        ':tdate' => date("Y-m-d", strtotime($data['t_date'])),
        ':tnotes' => htmlspecialchars($data['t_notes']),
        ':tpriv' => $data['t_privacy'],
        ':tid' => $data['tournament_id'] );
    $success = sql_try("UPDATE tblTournament SET tournament_name = :tname, tournament_date = :tdate,
    tournament_notes = :tnotes, tournament_privacy = :tpriv WHERE tournament_id = :tid", $bind_vars);
    return $success;
}

function tournament_delete($data, $aid) {
  // TODO IMMEDIATE -- delete modules
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

// adds a round to a tournament
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

// Add a tournament game to the database
function tournament_add_game($rid, $list, $db=null) {
    ($db) || ($db = connect_to_db());  // make sure we've got a connection

    // deletes bye v bye, and makes team v bye into just array(team)
    // TODO: don't like.  should've already done this. it's just here for manual game add
    foreach ($list as $idx => $team) {
        if ((! isset($team['id'])) || ($team['id'] == -1))
            unset($list[$idx]);
    }
    if (count($list) == 0) return;

    $gid = sql_insert("INSERT INTO tblGame (round_id, game_time) VALUES (:rid, NOW())", array(":rid" => $rid), $db);
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
    if (! $round) return false;
    require_privs(tournament_isadmin($round['tournament_id'], $aid));

    $games = sql_select_all("SELECT * FROM tblGame WHERE round_id = :rid", array(":rid" => $rid), $db);
    if (count($games)) {
        $success = sql_try("DELETE FROM tblGame WHERE round_id = :rid", array(":rid" => $rid), $db);
        foreach ($games as $g) {
            $success &= sql_try("DELETE FROM tblGameTeams WHERE game_id = :gid", array(":gid" => $g['game_id']), $db);
        }
        return $success;
    } 
    else return true;
}

// populates a round
function tournament_populate_round($tid, $rid, $aid) {
    $db = connect_to_db();

    // if we're not passed an $rid, make a new round and populate
    if (!$rid)
        ($rid = tournament_add_round($tid)) || die("<h1>Failed to add tournament round to populate</h1>");

    // Redundant check(rid && tid) to prevent rid phishing from remote tid
    $round = get_tournament_round($tid, $rid, $db);
    if ($round) {
        require_privs(tournament_isadmin($tid, $aid));
        $pairs = tournament_get_pairings($tid);
        foreach ($pairs as $p)
            tournament_add_game($rid, $p, $db);
    }
    return $rid;
}

function team_can_delete($team_id) {
    return !is_array(sql_select_one("SELECT * FROM tblGameTeams WHERE team_id = ?", array($team_id)));
}

function team_delete($team_id) {
    //TODO Should check ELSEWHERE:  team_id  exists and is for a team in tournament_id
    if (team_can_delete($team_id))
        return sql_try("DELETE FROM tblModuleTeams WHERE team_id = ?", array($team_id)) && 
               sql_try("DELETE FROM tblTeam WHERE team_id = ?", array($team_id));
               
}

function teams_import($data, $aid) {
    $status = false;

    if (! $data['imp_tid'])
        return false;

    if (intval($data['imp_num'] > 0)) {
        require_privs(tournament_isadmin($data['imp_tid'], $aid));
        $standings = get_standings($data['imp_tid'], true);

        array_multisort(array_map(function($t) {return $t['rank'];}, $standings), SORT_NUMERIC, $standings);
        $imp = array_slice($standings,0,intval($data['imp_num']));
        $status = true;
        foreach ($imp as $k => $t) {
            $nrounds = count($t['opponents']);
            // calculate an init value that can be compared over multiple tournaments
            if ($nrounds == 0) $init = $k+1;
            else               $init = 1000*($k+1)+1000*(1 - $t['score'] / $nrounds) +100*(1 - $t['buchholz'] / ($nrounds * $nrounds))+10*(1 - $t['berger'] / ($nrounds * $nrounds));
            //$init = intval($t['srs'] * -1000+100000);
            $status &= team_add( array('name_add' => $t['name'],
                                    'uid_add'  => $t['uid'],
                                    'init_add' => intval($init),
                                    'text_add' => $t['text'],
                                    'tournament_id' => $data['tournament_id']) );
        }
    }
    elseif (strlen($data['imp_url']))
        $status = teams_import_xml($data);
    return $status;
}

function validate_url($attempt) {
    $allowed_schemes = array('http','https');
    $url = parse_url($attempt);
    if (! $url['host'])   return false;
    if (! in_array($url['scheme'], $allowed_schemes))   return false;
    $build_url = "{$url['scheme']}://{$url['host']}/{$url['path']}";
    if ($url['query'])    $build_url="$build_url?{$url['query']}";
    if ($url['fragment']) $build_url="$build_url#{$url['fragment']}";
    return $build_url;
}

function teams_import_xml($data) {
    // make sure we've got a well-formed url for import
    $status = false;
    $url = validate_url($data['imp_url']);
    if ($url) {
        $xml = simplexml_load_file($url);
        if ($xml) {
            $status = true;
            $team = array('tournament_id' => $data['tournament_id'], 'init_add' => 0);
            foreach ($xml->team as $t) {
                $text = array();
                foreach ($t->player as $p)
                    $text[] = (string) $p;
                $team['name_add'] = (string) $t['name'];
                $team['uid_add'] = (string) $t['id'];
                $team['text_add'] = implode($text, ", ");
                $status &= team_add($team);
            }
        }
    }
    return $status;
}


// ASSERT: require_privs($data['tournament_id'], $aid)
function team_add($data) {
    $bind_vars = array(':tname' => htmlspecialchars($data['name_add']), 
                       ':tuid'  => htmlspecialchars($data['uid_add']), 
                       ':tinit' => intval($data['init_add']), 
                       ':ttext' => htmlspecialchars($data['text_add']), 

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
    $bind_vars = array(':tid'   => $tid,
                       ':toid'  => $data["tournament_id"],

                       ':tname' => htmlspecialchars($data["name_$tid"]),
                       ':tuid'  => htmlspecialchars($data["uid_$tid"]),
                       ':tinit' => intval($data["init_$tid"]),
                       ':ttext' => htmlspecialchars($data["text_$tid"]));
    // checking :tid/:toid against tournament_id phishing
    $success = sql_try("UPDATE tblTeam SET team_name = :tname, team_text = :ttext, team_uid = :tuid,
                        team_init = :tinit WHERE team_id = :tid AND tournament_id = :toid", $bind_vars);
    return $success;
}

function tournament_update_score( $gid, $data) {
    // TODO: adjust timestamp for last-modified
    // grab tournament_id corresponding to $gid, then require_privs
    $mode = get_tournament_mode($data['tournament_id']);
    $teams = sql_select_all("SELECT * FROM tblGameTeams WHERE game_id = :gid", array(":gid" => $gid));

    // Make sure we're not a tie if in elim mode
    if (($mode == 1) || ($mode == 2)) {
        $tie = true;
        foreach ($teams as $team) {
            $tid = $team['team_id'];
            $score = $data["score_$tid"];
            if ($score == "") { $score = -1; }
            else              { $score = intval($score); }

            if (isset($check) && ($score != $check))
                $tie = false;
            $check = $score;
        }
        if ($tie && ($score != -1)) {
            debug_alert("Tie game not permitted in elimination tournament");
            return;
        }

    }

    foreach ($teams as $team) {
        $tid = $team['team_id'];
        $score = $data["score_$tid"];
        if ($score == "") { $score = -1; }
        else              { $score = intval($score); }
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
  // should look more closely at this SELECT - I think we want something more like round_id != rid
  $opponents = sql_select_all("SELECT b.team_id from tblGameTeams a JOIN tblGameTeams b USING (game_id) WHERE a.team_id = :tid AND a.score != -1 AND b.score != -1", array(":tid" => $teams[0]['team_id']));
  foreach ($opponents as $opp)  // should check against not just teams[1], but teams[2, etc]
    if ($opp['team_id'] == $teams[1]['team_id'])
        return true;
  return false;
}

?>
