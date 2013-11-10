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
    $dest = "/private/";
    try {
        if (check_login()) {
            switch ($_POST['case']) {
                case 'NewTournament':
                    require_login();
                    $new_id = new_tournament();
                    $dest = "/private/tournament/$new_id/";
                    break;
                case 'DelTournament':
                    require_privs(tournament_isowner($_POST['tournament_id']));
                    tournament_delete($_POST['tournament_id']);
                    break;
                case 'DelModule':
                    $m = get_module($_POST['module_id']);
                    module_delete($m['module_id']);
                    $dest = "/private/tournament/{$m['parent_id']}/";
                    break;
                case 'DelSelf':
                    sql_try("DELETE FROM tblTournamentAdmins WHERE tournament_id = ? AND admin_id = ?", array($_POST['tournament_id'], $_SESSION['admin_id']));
                    break;
                default:
                    throw new Exception("Unexpected Sync-Post case: {$_POST['case']}");
            }
        }
    } catch (Exception $e) {
        $status = array( "errno" => $e->getCode(), "msg" => $e->getMessage());
        die($status['msg']); //TODO better failure handling here
    }
    header("location:$dest");
?>
