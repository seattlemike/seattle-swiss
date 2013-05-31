<?php 
	include("header.php"); 
    require_once("utils_async.php");

    $return_data = array("success" => true);
    try {
        if (check_login()) {
            switch ($_POST['case']) {
                case 'ModuleTeamAdd':  // add team_id to the teams competing in module_id
                    asyncModuleTeamAdd($_POST['module_id'], $_POST['team_id'], $_POST['team_seed']);
                    break;
                case 'ModuleTeamDel':  // add team_id to the teams competing in module_id
                    asyncModuleTeamDel($_POST['module_id'], $_POST['team_id']);
                    break;
                case 'GameUpdate':  // update score/status for game
                    asyncGameUpdate(json_decode($_POST['game_data'], true), json_decode($_POST['score_data'], true));
                    break;
                case 'AddRound':  // update score/status for game
                    $retval = asyncNextRound($_POST['module_id']);
                    if ($retval)
                        $return_data = array_merge($return_data, $retval);
                    else
                        echo "fail";
                    break;
                default:
                    throw new Exception("Unexpected ASYNC case: {$_POST['case']}");
            }
        }
    } catch (Exception $e) {
        $status = array( "errno" => $e->getCode(), "msg" => $e->getMessage());
        die(json_encode($status));
    }
    echo json_encode($return_data);
?>
