<?php 
	include("header.php"); 
    require_once("utils_async.php");

    if (check_login()) {
        switch ($_POST['case']) {
            case 'ModuleTeamAdd':  // add team_id to the teams competing in module_id
                $status = asyncModuleTeamAdd($_POST['module_id'], $_POST['team_id'], $_POST['team_seed']);
                break;
            case 'ModuleTeamDel':  // add team_id to the teams competing in module_id
                $status = asyncModuleTeamDel($_POST['module_id'], $_POST['team_id']);
                break;
            case 'GameUpdate':  // update score/status for game
                $status = asyncGameUpdate(json_decode($_POST['game_data'], true), json_decode($_POST['score_data'], true));
                break;
            default:
                $status = array("errno" => 1, "msg" => "Unexpected ASYNC case \"{$_POST['case']}\"");
        }

        if ($status)
            echo json_encode($status);
    }
?>
