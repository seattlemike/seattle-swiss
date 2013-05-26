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
    $js_extra = array("/ui.js", "/async.js", "/module.js");
    $header_extra = array( '<script type="text/javascript">window.onload = moduleOnLoad</script>' );
    disp_header("{$tourney['tournament_name']} : {$module['module_title']}", $js_extra, $header_extra);
    disp_topbar($tourney, $module, 1);
    disp_titlebar("{$module['module_title']}");

    //ASSERT: mid/tid are valid and we have privs to edit
    if (isset($_POST['case'])) {
        $_POST['tournament_id'] = $tid;
        $_POST['module_id'] = $mid;
        switch($_POST['case']) {
            case 'delete_module':
                module_delete($mid);
                header_redirect("/private/tournament/$tid/");
                break;
            case 'update_module':
                module_update($_POST);
                header_redirect("/private/module/$mid/");
                break;
            case 'update_seeds':
                module_update_seeds($_POST);
                header_redirect("/private/module/$mid");
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
                <input type='hidden' name='case' value='update_module' />
                <input type='hidden' name='module_id' value='<? echo $mid?>' />
                <?php
                    disp_module_details($module);
                    echo "<div class='line'>";
                    disp_tournament_button("Save Details", 'update_module'); 
                    echo "</div>";
                    if (tournament_isowner($tid))
                        echo "<a id='del-btn' class='button'>Delete Round</a>";
                ?>
            </form>
        </div>

        <div class="mainBox">
            <?php
                $tournament_teams = get_tournament_teams($tid, "team_id");
                if (count($tournament_teams)) {
                    disp_module_teams($mid, $tournament_teams);
                } else {
                    echo "<div class='header'>Tournament has no teams yet</div>\n";
                    echo "<a href='/private/tournament/$tid/' class='button'>Back</a>";
                }
                ?>
        </div>
        <div class="nav rHead">
            <div class='header'></div>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
