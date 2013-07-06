<?php

function asyncError($errno, $msg) {
    return array("errno" => $errno, "msg" => $msg);
}

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

function asyncModuleTeamAdd($mid, $team_id, $seed) {
    $module = get_module($mid);
    checkTournamentPrivs($module['parent_id']);
    checkTournamentTeam($module['parent_id'], $team_id);
    if (! module_hasteam($mid, $team_id))
        sql_try("INSERT INTO tblModuleTeams (module_id, team_id, team_seed) VALUES (?, ?, ?)", array($mid, $team_id, $seed));
}

function asyncModuleTeamDel($mid, $team_id) {
    $module = get_module($mid);
    checkTournamentPrivs($module['parent_id']);
    checkTournamentTeam($module['parent_id'], $team_id);
    if (module_hasteam($mid, $team_id))
        sql_try("DELETE FROM tblModuleTeams WHERE module_id = ? AND team_id = ?", array($mid, $team_id));
}

function asyncGameUpdate($game_data, $score_data) {
    $game = get_game($game_data['game_id']);
    $round = get_round($game['round_id']);
    $module = get_module($round['module_id']);
    checkTournamentPrivs($module['parent_id']);
    foreach ($score_data as $score)
        sql_try("UPDATE tblGameTeams SET score = ? WHERE game_id = ? AND team_id = ?", array($score['score'], $game['game_id'], $score['team_id']));
    sql_try("UPDATE tblGame SET status = ?, game_time = NOW() WHERE game_id = ?", array($game_data['status'], $game['game_id']));
}

function asyncAddRound($mid) {
    $module = get_module($mid);
    checkTournamentPrivs($module['parent_id']);
    $status = sql_select_one("SELECT MAX(ABS(1-status)) value FROM tblGame JOIN tblRound USING (round_id) WHERE module_id = ?", array($mid));
    // if all status are 0 (or if no entries at all), add/populate new round
    if ($status['value'] == 0) {
        $rid = module_add_round($mid);
        round_populate($rid);
        $games = array_map( 
            function ($g) { return array( "game_data" => $g, 
                                "score_data" => sql_select_all("SELECT * from tblGameTeams a JOIN tblTeam b using (team_id) WHERE a.game_id = ? ORDER BY a.score_id DESC", array($g['game_id']))); },
            get_round_games($rid));
        return(array("round" => get_round($rid), "games" => $games));
    }
}

function asyncTrnNewTeam($tid, $name, $text, $uid) {
    checkTournamentPrivs($tid);
    return(array("teamId" => sql_insert("INSERT INTO tblTeam (tournament_id, team_name, team_text, team_uid) VALUES (?, ?, ?, ?)", array($tid, $name, $text, $uid))));
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

function asyncTrnUpdTeam($tid, $team) {
    checkTournamentPrivs($tid);
    checkTournamentTeam($tid, $team['team_id']);
    sql_try("UPDATE tblTeam set team_name = ?, team_text = ?, team_uid = ? WHERE team_id = ?", array($team['team_name'], $team['team_details'], $team['team_uid'], $team['team_id']));
}

function asyncAddGame($mid, $a_id, $b_id) {
    $module = get_module($mid);
    checkTournamentPrivs($module['parent_id']);
    checkTournamentTeam($module['parent_id'], $a_id);
    $select = sql_select_one("SELECT MAX(round_id) AS latest FROM tblRound WHERE module_id = ?", array($mid));
    $teams[0] = array("id" => $a_id);
    if ($b_id) {
        checkTournamentTeam($module['parent_id'], $b_id);
        $teams[1] = array("id" => $b_id);
    }
    $game_id = round_add_game($select['latest'], $teams);
    return array("game_data" => get_game($game_id), "score_data" => get_game_scores($game_id));
}

function asyncDelGame($game_id) {
    checkGamePrivs($game_id);
    sql_try("DELETE FROM tblGameTeams WHERE game_id = ?", array($game_id));
    sql_try("DELETE FROM tblGame WHERE game_id = ?", array($game_id));
}

function asyncDelRound($round_id) {
    checkRoundPrivs($round_id);
    // CHECK EMPTY ROUND?
    sql_try("DELETE FROM tblRound WHERE round_id = ?", array($round_id));
}

?>
