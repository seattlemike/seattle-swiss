<?php

function asyncError($errno, $msg) {
    return array("errno" => $errno, "msg" => $msg);
}

function dbAttempt($query, $vars) {
    if (! sql_try($query, $vars))
        throw new Exception("Database access failed on: $query");
}

function checkTournamentPrivs($tid) {
    if (! tournament_isadmin($tid))
        throw new Exception("Not admin for tournament [$tid]");
    return true;
}


function checkTeamPrivs($mid, $team_id) {
    $module = get_module($mid);
    checkTournamentPrivs($module['parent_id']);
    if (! tournament_hasteam($module['parent_id'], $team_id))
        throw new Exception("Couldn't find team [$team_id] in tournament [{$module['parent_id']}]");
    return true;
}

function asyncModuleTeamAdd($mid, $team_id, $seed) {
    checkTeamPrivs($mid, $team_id);
    if (! module_hasteam($mid, $team_id))
        dbAttempt("INSERT INTO tblModuleTeams (module_id, team_id, team_seed) VALUES (?, ?, ?)", array($mid, $team_id, $seed));
}

function asyncModuleTeamDel($mid, $team_id) {
    checkTeamPrivs($mid, $team_id);
    if (module_hasteam($mid, $team_id))
        dbAttempt("DELETE FROM tblModuleTeams WHERE module_id = ? AND team_id = ?", array($mid, $team_id));
}

function asyncGameUpdate($game_data, $score_data) {
    $game = get_game($game_data['game_id']);
    $round = get_round($game['round_id']);
    $module = get_module($round['module_id']);
    checkTournamentPrivs($module['parent_id']);
    foreach ($score_data as $score)
        dbAttempt("UPDATE tblGameTeams SET score = ? WHERE game_id = ? AND team_id = ?", array($score['score'], $game['game_id'], $score['team_id']));
    dbAttempt("UPDATE tblGame SET status = ?, game_time = NOW() WHERE game_id = ?", array($game_data['status'], $game['game_id']));
}

function asyncNextRound($mid) {
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

?>
