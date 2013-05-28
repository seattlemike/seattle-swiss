<?php

function asyncError($errno, $msg) {
    return array("errno" => $errno, "msg" => $msg);
}

function dbAttempt($query, $vars) {
    if (sql_try($query, $vars))
        return array("success" => true);
    else
        return asyncError(50, "database access failed");
}


function asyncModuleTeamPrivs($mid, $team_id) {
    if (! $module = get_module($mid))
        return asyncError(20, "Couldn't find bracket");
    elseif (! tournament_isadmin($module['parent_id']) )
        return asyncError(100, "Not admin for tournament {$module['parent_id']}");
    elseif (! tournament_hasteam($module['parent_id'], $team_id))
        return asyncError(22, "Coudln't find team in tournament");
    else
        return true;
}

function asyncModuleTeamAdd($mid, $tid, $seed) {
    if (is_array($status = asyncModuleTeamPrivs($mid, $tid)))
        return $status;
    elseif (module_hasteam($mid, $tid))
        return asyncError(24, "Team already present in this module");
    return dbAttempt("INSERT INTO tblModuleTeams (module_id, team_id, team_seed) VALUES (?, ?, ?)", array($mid, $tid, $seed));
}

function asyncModuleTeamDel($mid, $tid) {
    if (is_array($status = asyncModuleTeamPrivs($mid, $tid)))
        return $status;
    elseif (! module_hasteam($mid, $tid))
        return asyncError(23, "Couldn't find team enabled in this module");
    return dbAttempt("DELETE FROM tblModuleTeams WHERE module_id = ? AND team_id = ?", array($mid, $tid));
}

function asyncGameUpdate($game_data, $score_data) {
    if (! $game = get_game($game_data['game_id']))
        return asyncError(30, "Couldn't find game: {$data['game_id']}");
    if (! $round = get_round($game['round_id']))
        return asyncError(30, "Couldn't find round {$data['round_id']} for game {$data['game_id']}");
    if (! $module = get_module($round['module_id']))
        return asyncError(30, "Couldn't find module {$data['module_id']} for game {$data['game_id']}");
    
    if (! tournament_isadmin($module['parent_id']))
        return asyncError(100, "Not admin for tournament {$module['parent_id']}");
    else {
        foreach ($score_data as $score)
            dbAttempt("UPDATE tblGameTeams SET score = ? WHERE game_id = ? AND team_id = ?", array($score['score'], $game['game_id'], $score['team_id']));
        return dbAttempt("UPDATE tblGame SET status = ?, game_time = NOW() WHERE game_id = ?", array($game_data['status'], $game['game_id']));
    }
}

?>
