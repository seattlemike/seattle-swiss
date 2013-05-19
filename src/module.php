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
	include("header.php");

    // Ensure mid/tid are valid and that we have privs to edit
    ($mid = $_POST['module_id']) || ($mid = $_GET['module']);
    $module = get_module($mid) ;
    if (! $module) 
        header_redirect("main_menu.php");
    $tid = $module['parent_id'];
	require_login();
    require_privs( tournament_isadmin($tid, $_SESSION['admin_id']) );
    $tourney = get_tournament($tid);

    // logged in / have privs, so display header
    $js_extra = array("ui.js", "async.js", "module.js");
    $header_extra = array( '<script type="text/javascript">window.onload = moduleOnLoad</script>' );
    disp_header("{$tourney['tournament_name']} : {$module['module_title']}", $js_extra, $header_extra);
    disp_topbar($tourney, $module, 1);
    disp_titlebar("{$module['module_title']}");

    //ASSERT: mid/tid are valid and we have privs to edit
    if (isset($_POST['action'])) {
        $_POST['tournament_id'] = $tid;
        $_POST['module_id'] = $mid;
        switch($_POST['action']) {
            case 'delete_module':
                die("Delete module");
                //$redir = module_delete($_POST, $_SESSION['admin_id']);
                //$redir || die("Failed module delete!");
                break;
            case 'update_module':
                module_update($_POST);
                header_redirect("module.php?module=$mid");
                break;
            case 'update_seeds':
                module_update_seeds($_POST);
                header_redirect("module.php?module=$mid");
                break;
        }

    }
?>
<div class="con">
    <div class="centerBox">
        <?  //disp_status($tourney) ?>
        <div class="mainBox">
            <div class="header">Details</div>
            <form name="details" method="post" action="">
                <input type='hidden' name='action' value='update_tournament' />
                <input type='hidden' name='module_id' value='<? echo $mid?>' />
                <?php
                    disp_module_details($module);
                    echo "<div class='line'>";
                    disp_tournament_button("Save Details", 'update_module'); 
                    echo "</div>";
                    if (tournament_isowner($tid, $_SESSION['admin_id']))
                        echo '<a class="button" onClick="delBracket()">Delete Bracket</a>';
                ?>
            </form>
        </div>

        <div class="mainBox">
            <div id='teams' class="header">Competing this round</div>
            <form name='teams' method='post' action="">
                <input type='hidden' name='action' value='update_seeds' />
                <input type='hidden' name='module_id' value='<? echo $mid?>' />
                <table>
                <tr><th>Team Name</th><th>Status</th><th>Seed</th></tr>
                <?php
                $module_teams = get_module_teams($mid); // sorted by seed
                $module_team_ids = array();
                foreach ($module_teams as $idx => $team) {
                    $team['team_seed'] = $idx+1; // regular 1..n seeds
                    disp_module_team($team);
                    $module_team_ids[] = $team['module_id'];
                }
                foreach (get_tournament_teams($tid, "team_id") as $team) {
                    if (! module_hasteam($mid, $team['team_id'])) {
                        $team['disabled'] = "DISABLED";
                        disp_module_team($team);
                    }
                }
                ?>
                </table>
            </form>
            <div class='line'><a class='button disabled' id="save-seeds">Save Seeds</a></div>
        </div>
        <div class="nav rHead">
            <div class='header'></div>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
