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
	require_login();
    require_privs( tournament_isadmin($module['parent_id']) );

    $tid = $module['parent_id'];
    $tourney = get_tournament($tid);

    // Display header
    $js_extra = array("/ui.js", "/async.js", "/module_ui.js");
    $header_extra = array( '<script type="text/javascript">window.onload = moduleOnLoad</script>' );
    disp_header("{$tourney['tournament_name']} : {$module['module_name']}", $js_extra, $header_extra);
    disp_topbar($tourney, $module, 1);
    disp_titlebar("{$module['module_name']}");

    // old post functions:
    //module_delete($mid);
    //module_update($module, $_POST);
    //module_update_seeds($_POST);
?>
<div class="con">
    <div class="centerBox">
        <?php
        //disp_status($tourney)
        $isowner = ( $tourney['tournament_owner'] == $_SESSION['admin_id'] );
            echo "<div id='module-details' data-mid='$mid' data-owner='$isowner' class='mainBox'>";
            $modes = array("Swiss Rounds", "Single Elimination", "Double Elimination", "Round Robin");
            echo "<div class='header'>Module Details</div>";
            echo "<div class='item'>".date("M d, Y", strtotime($module['module_date']))."</div>";
            echo "<div class='item'>{$module['module_name']}</div>";
            echo "<div class='item'>{$module['module_text']}</div>";
            echo "<div class='item'>{$modes[$module['module_mode']]}</div>";
            ?>
            <a class='button' id='edit-details'>Edit</a>
        </div>
        <div id='module-teams' data-teams='<? echo json_encode(get_tournament_teams($tid), JSON_HEX_APOS) ?>' class="mainBox">
            <div class="header">Module Teams</div>
            <?php
                echo "<div id='team-list'>";
                foreach (get_module_teams($mid) as $t)
                    echo "<div data-id='{$t['team_id']}' class='item'>{$t['team_name']}</div>";
                echo "</div>";
            ?>
            <a class='button' id='edit-teams'>Add/Remove</a>
            <a class='button' id='edit-seeds'>Seeding</a>
            </div>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
