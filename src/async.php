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

	include("header.php"); 
    require_once("utils_async.php");

    $return_data = array("success" => true);
    $retval = array();
    try {
        if (check_login()) {
            switch ($_POST['case']) {
                // Async Tournament
                case 'UpdateTournament':
                    $retval = asyncTrnUpdate($_POST['tournament_id'], $_POST);
                    break;
                case 'AddAdmin':
                    $retval = asyncAddAdmin($_POST['tournament_id'], $_POST['admin_email']);
                    break;
                case 'RemoveAdmin':
                    asyncRemoveAdmin($_POST['tournament_id'], $_POST['admin_email']);
                    break;
                case 'NewModule':
                    $retval = asyncNewModule($_POST['tournament_id']);
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

                // Async Module
                case 'UpdateModule':
                    asyncModuleUpdate($_POST['module_id'], $_POST);
                    break;
                case 'ModuleTeams': // add/remove teams from the module
                    asyncModuleTeams($_POST['module_id'], json_decode($_POST['team_data']));
                    break;
                case 'ModuleSeeds':
                    asyncModuleSeeds($_POST['module_id'], json_decode($_POST['team_data']));
                    break;

                // Async Run-module
                case 'AddRound':  // update score/status for game
                    $retval = asyncAddRound($_POST['module_id']);
                    break;
                case 'DeleteRound':
                    asyncDelRound($_POST['round_id']);
                    break;
                case 'AddGame':
                    $retval = asyncAddGame($_POST['module_id'], json_decode($_POST['team_data'], true));
                    break;
                case 'UpdateGame':  // update score/status for game
                    asyncUpdateGame(json_decode($_POST['game_data'], true), json_decode($_POST['score_data'], true));
                    break;
                case 'DeleteGame':
                    asyncDelGame($_POST['game_id']);
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
