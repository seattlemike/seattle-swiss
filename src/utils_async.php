<?php
/*  
    Copyright 2011, 2012, 2013 Mike Bell and Paul Danos

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

// Check privs for various operations
function checkRoundPrivs($round_id) {
    $round = get_round($round_id);
    $tid = get_module_parent($round['module_id']);
    checkTournamentPrivs($tid);
}

function checkGamePrivs($game_id) {
    $game = get_game($game_id);
    checkRoundPrivs($game['round_id']);
}

function checkTournamentPrivs($tid) {
    if (! tournament_isadmin($tid))
        throw new Exception("Not admin for tournament [$tid]");
}

function checkTournamentTeam($tid, $team_id) {
    if (! tournament_hasteam($tid, $team_id))
        throw new Exception("Could not find team [$team_id] in tournament [$tid]");
}

// *****
// Functions called from async.php
// *****

// update everything but slug, then try to update slug for uniqueness
function asyncTrnUpdate($tid, $data) {
    checkTournamentPrivs($tid);
    sql_try("UPDATE tblTournament SET tournament_name=?, tournament_date=?, tournament_privacy=?, tournament_text=? WHERE tournament_id=?", 
            array($data['tournament_name'], date("Y-m-d", strtotime($data['tournament_date'])), $data['tournament_privacy'], $data['tournament_text'], $tid));
    try {
        sql_try("UPDATE tblTournament SET tournament_slug=? WHERE tournament_id=?",
                array($data['tournament_slug'], $tid));
    } catch(Exception $e) {
        return array("unique" => false);
    }
    return array("unique" => true);
}

// if Admin exists with $email, add to Tournament $tid
function asyncAddAdmin($tid, $email) {
    if (! tournament_isowner($tid))
        throw new Exception("Must be tournament owner to Add Admin");
    // find in db
    $adm = sql_select_one("SELECT * FROM tblAdmin WHERE admin_email = ?", array($email));
    if ($adm) {
        // make sure not currently in tournament
        $already = sql_select_one("SELECT * FROM tblTournamentAdmins WHERE tournament_id = ? AND admin_id = ?", array($tid, $adm['admin_id']));
        if (! $already) {
            sql_insert("INSERT INTO tblTournamentAdmins (tournament_id, admin_id) VALUES (?, ?)", array($tid, $adm['admin_id']));
            return array("adminName" => $adm['admin_name'], "adminEmail" => $adm['admin_email'], "adminId" => $adm['admin_id']);
        }
    }
    return array("added" => false);
}
function asyncRemoveAdmin($tid, $email) {
    if (! tournament_isowner($tid))
        throw new Exception("Must be tournament owner to Remove Admins");
    $adm = sql_select_one("SELECT * FROM tblAdmin WHERE admin_email = ?", array($email));
    if ($adm && ($adm['admin_id'] != $_SESSION['admin_id'])) // don't remove self
        sql_try("DELETE FROM tblTournamentAdmins WHERE admin_id = ? AND tournament_id = ?", array($adm['admin_id'], $tid));
}

// Add/Delete Modules for Tournament $tid
function asyncNewModule($tid) {
    checkTournamentPrivs($tid);
    $mid = sql_insert("INSERT INTO tblModule (module_name, module_date, parent_id) 
                       VALUES (?, (SELECT tournament_date FROM tblTournament WHERE tournament_id=?), ?)",
                      array("New Module", $tid, $tid));
    return array("moduleId" => $mid);
}

// Add/Update/Delete Teams for Tournament $tid
function asyncTrnNewTeam($tid, $name, $text, $uid) {
    checkTournamentPrivs($tid);
    return(array("teamId" => sql_insert("INSERT INTO tblTeam (tournament_id, team_name, team_text, team_uid) VALUES (?, ?, ?, ?)", array($tid, $name, $text, $uid))));
}

function asyncTrnUpdTeam($tid, $team) {
    checkTournamentPrivs($tid);
    checkTournamentTeam($tid, $team['team_id']);
    sql_try("UPDATE tblTeam set team_name = ?, team_text = ?, team_uid = ? WHERE team_id = ?", array($team['team_name'], $team['team_details'], $team['team_uid'], $team['team_id']));
}

function asyncTrnDelTeam($tid, $team_id) {
    checkTournamentPrivs($tid);
    checkTournamentTeam($tid, $team_id);
    $ngames = sql_select_one("SELECT COUNT(*) FROM tblGameTeams WHERE team_id = ?", array($team_id));
    if ($ngames[0]) { // can't delete if we've played games
        return array("deleted" => false);
    } else {
        sql_try("DELETE FROM tblTeam WHERE team_id = ?", array($team_id));
        return array("deleted" => true);
    }
}

// Update module details
function asyncModuleUpdate($mid, $data) {
    $module = get_module($mid);
    checkTournamentPrivs($module['parent_id']);
    //TODO REWRITE
    sql_try("UPDATE tblModule SET module_name=?, module_date=?, module_text=?, module_mode=? WHERE module_id=?", 
            array($data['module_name'], date("Y-m-d", strtotime($data['module_date'])), $data['module_text'], $data['module_mode'], $mid));
}

function asyncModuleSeeds($mid, $teams) {
    $module = get_module($mid);
    checkTournamentPrivs($module['parent_id']);
    foreach($teams as $t)
        sql_try("UPDATE tblModuleTeams SET team_seed=? WHERE team_id=? AND module_id=?", array($t->seed, $t->id, $mid));
}

function asyncModuleTeams($mid, $teams) {
    $module = get_module($mid);
    checkTournamentPrivs($module['parent_id']);
    foreach($teams as $t) {
        if (($t->state == 0) && (module_hasteam($mid, $t->id))) {
                sql_try("DELETE FROM tblModuleTeams WHERE module_id = ? AND team_id = ?", array($mid, $t->id));
        } elseif (($t->state == 1) && (! module_hasteam($mid, $t->id))) {
            $seed = sql_select_one("SELECT MAX(team_seed) maxseed FROM tblModuleTeams JOIN tblTeam USING (team_id) WHERE module_id = ?", array($mid));
            sql_try("INSERT INTO tblModuleTeams (module_id, team_id, team_seed) VALUES (?, ?, ?)", array($mid, $t->id, $seed["maxseed"] + 1));
        }
    }
}

// Add/Delete Rounds for Module $mid
function asyncAddRound($mid) {
    $module = get_module($mid);
    checkTournamentPrivs($module['parent_id']);
    $status = sql_select_one("SELECT MAX(ABS(1-status)) value FROM tblGame JOIN tblRound USING (round_id) WHERE module_id = ?", array($mid));
    // if all status are 0 (or if no entries at all), add/populate new round
    if ($status['value'] == 0) {
        $rid = module_add_round($mid);
        round_populate($rid);

        $data = get_round_data($rid, $module);
        foreach ($data as &$r) {
            $r['games'] = array_map( 
                function ($g) { return array( "game_data" => $g, 
                                    "score_data" => sql_select_all("SELECT * from tblGameTeams a JOIN tblTeam b using (team_id) WHERE a.game_id = ? ORDER BY a.score_id DESC", array($g['game_id']))); },
                $r['games']);
        }
        return array("rounds" => $data);
    }
    // FAILED TO ADD ROUND - NOT ALL STATUS ARE ZERO
}

function asyncDelRound($round_id) {
    checkRoundPrivs($round_id);
    // RECURSIVELY DELETE??
    sql_try("DELETE FROM tblRound WHERE round_id = ?", array($round_id));
}

// Add/Update/Delete Games for module $mid
function asyncAddGame($mid, $teams) {
    $module = get_module($mid);
    checkTournamentPrivs($module['parent_id']);
    foreach ($teams as $t)
        checkTournamentTeam($module['parent_id'], $t['id']);
    $select = sql_select_one("SELECT MAX(round_id) AS latest FROM tblRound WHERE module_id = ?", array($mid));
    $game_id = round_add_game($select['latest'], $teams);
    return array("game_data" => get_game($game_id), "score_data" => get_game_scores($game_id));
}

function asyncUpdateGame($game_data, $score_data) {
    $game = get_game($game_data['game_id']);
    checkRoundPrivs($game['round_id']);
    foreach ($score_data as $score)
        sql_try("UPDATE tblGameTeams SET score = ? WHERE game_id = ? AND team_id = ?", array($score['score'], $game['game_id'], $score['team_id']));
    sql_try("UPDATE tblGame SET status = ?, game_time = NOW() WHERE game_id = ?", array($game_data['status'], $game['game_id']));
}

function asyncDelGame($game_id) {
    $game = get_game($game_id);
    if ($game) {
        checkRoundPrivs($game['round_id']);
        sql_try("DELETE FROM tblGameTeams WHERE game_id = ?", array($game_id));
        sql_try("DELETE FROM tblGame WHERE game_id = ?", array($game_id));
        // now delete round if empty
        $count = sql_select_one("SELECT COUNT(*) FROM tblGame WHERE round_id = ?", array($game['round_id']));
        if (! $count[0])
            sql_try("DELETE from tblRound WHERE round_id = ?", array($game['round_id']));
    }
}

?>
