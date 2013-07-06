<?php 
	include("header.php"); 
    require_once("utils_async.php");

    $return_data = array("success" => true);
    $retval = array();
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
                    $retval = asyncAddRound($_POST['module_id']);
                    break;
                case 'NewTeam':
                    $retval = asyncTrnNewTeam($_POST['tournament_id'], $_POST['team_name'], $_POST['team_details'], $_POST['team_uid']);
                    break;
                case 'DeleteTeam':
                    $retval = asyncTrnDelTeam($_POST['tournament_id'], $_POST['team_id']);
                    break;
                case 'UpdateTeam':
                    asyncTrnUpdTeam($_POST['tournament_id'], $_POST);
                    break;
                case 'DeleteGame':
                    asyncDelGame($_POST['game_id']);
                    break;
                case 'AddGame':
                    $retval = asyncAddGame($_POST['module_id'], $_POST['a_id'], $_POST['b_id']);
                    break;
                case 'DeleteRound':
                    asyncDelRound($_POST['round_id']);
                    break;
                default:
                    throw new Exception("Unexpected ASYNC case: {$_POST['case']}");
            }
        }
    } catch (Exception $e) {
        $status = array( "errno" => $e->getCode(), "msg" => $e->getMessage());
        die(json_encode($status));
    }
    $return_data = array_merge($return_data, $retval);
    echo json_encode($return_data, JSON_HEX_APOS);
?>
