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

	include("header.php");  // sets $tid if valid tournament
    $tid = get_tid();
    if (! $tid) 
        header_redirect("location:/private/");
    $_POST['tournament_id'] = $tid;

	require_login();
    require_privs( tournament_isadmin($tid) );
    $tourney = get_tournament($tid);

    $js_extra = array("/ui.js", "/tournament_ui.js", "/async.js");
    $header_extra = array( '<script type="text/javascript">window.onload = tournamentOnLoad</script>' );
    disp_header($tourney['tournament_name'], $js_extra, $header_extra);
    disp_topbar($tourney, null, 0);
    disp_titlebar($tourney['tournament_name']);

    //   ASSERT $_POST[tournament_id] is set to tid
    if (isset($_POST['case'])) {
        switch ($_POST['case']) {
            case 'delete_tournament':
                tournament_delete($tourney['tournament_id']);
                header_redirect("/private/");
                break;
            case 'new_module':
                if (! tournament_new_module($_POST))
                    echo "<div class='header warning'>Failed to add new round</div>";
                break;
            case 'update_tournament':
                tournament_update($_POST);
                header("location:/private/tournament/$tid/");
                break;
            case 'add_admin':
                if (! tournament_add_admin($_POST, $_SESSION['admin_id']))
                    echo "<div class='header warning'>Failed to add admin</div>";
                break;
            case 'remove_admin':
                if (! tournament_remove_admin($_POST, $_SESSION['admin_id']))
                    echo "<div class='header warning'>Failed to remove admin</div>";
                break;
        }
        if ($redir) header("location:/private/");
    }
?>
<div class="con">
    <div class="centerBox">
        <?  //disp_status($tourney) ?>
        <div id="modules" class="mainBox">
            <div class="header">Tournament Modules</div>
            <div id='modules-list'>
            <?php
                $t_modules = get_tournament_modules($tid);
                if (! $t_modules)
                    echo "<p>No modules scheduled yet</p>";
                disp_modules_list($t_modules);
            ?>
            </div>
            <a class='button' id='add-module'>Add Module</a>
            <? /*
            <form name="modules" method="post" action="">
                <input type='hidden' name='case' value='new_module' />
                <input class='button' type='submit' name='add_round' value="Add Round">
            </form>
            */ ?>
        </div>

        <div class="mainBox" id="details" data-tid="<? echo $tid ?>">
            <div class="header">Tournament Details</div>
            <?php
            echo "<div class='item'>".date("M d, Y", strtotime($tourney['tournament_date']))."</div>";
            echo "<div class='item'>{$tourney['tournament_name']}</div>";
            echo "<div class='item'>{$tourney['tournament_notes']}</div>";
            $privacy = array("Private", "Link-only", "Public");
            echo "<div class='item'>Display: <i>{$privacy[$tourney['tournament_privacy']]}</i></div>";
            ?>
            <a class='button' id='edit-details'>Edit</a>
        </div>

        <div id="admins" class="mainBox">
            <div class="header">Tournament Admins</div>
            <? 
                disp_admins_list($tourney);
                if ($tourney['tournament_owner'] == $_SESSION['admin_id'])
                    echo "<a class='button' id='edit-admins'>Edit</a>";
            ?>
        </div>
        <div class="mainBox" id="teams">
            <div class="header">Tournament Teams</div>
            <?  
                $teams = get_tournament_teams($tid);
                disp_teams_list($teams);
            ?>
            <a class='button' id='add-teams'>Add Teams</a>
            <a class='button' id='add-team'>+1</a>
        </div>
        <? /*
        <div class="nav rHead">
            <form id="delForm" name='delete' method='post' action=''>
                <input type='hidden' name='case' value='delete_tournament' />
                <?php 
                    if (tournament_isowner($tourney['tournament_id']))
                        echo '<a href="#" onClick="delTournament()">Delete Tournament</a>';
                ?>
            </form>
            <div class='header'></div>
        </div>
        */ ?>
    </div>
</div>
<?php include("footer.php"); ?>
