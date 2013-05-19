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
    if (! $tid) header("location:main_menu.php");

	require_login();
    require_privs( tournament_isadmin($tid, $_SESSION['admin_id']) );
    $tourney = get_tournament($tid);

    $js_extra = array("ui.js");
    disp_header($tourney['tournament_name'], $js_extra);
    disp_topbar($tourney, null, 0);
    disp_titlebar($tourney['tournament_name']);

    //TODO: validate tournament details
    if (isset($_POST['action'])) {
        $_POST['tournament_id'] = $tid;  //this is not a bad thing

        if ($_POST['action'] == 'delete_tournament') {
            $redir = tournament_delete($_POST, $_SESSION['admin_id']);
            $redir || die("Failed tournament delete!");
        }
        elseif ($_POST['action'] == 'new_module') {
            if (! tournament_new_module($_POST, $_SESSION['admin_id']))
                echo "<div class='header warning'>Failed to add new round</div>";
            }
        elseif ($_POST['action'] == 'update_tournament') {
            tournament_update($_POST, $_SESSION['admin_id']);
            header("location:tournament.php?id=$tid");
        }
        elseif ($_POST['action'] == 'add_admin') {
            if (! tournament_add_admin($_POST, $_SESSION['admin_id']))
                echo "<div class='header warning'>Failed to add admin</div>";
            }
        elseif ($_POST['action'] == 'remove_admin') {
            if (! tournament_remove_admin($_POST, $_SESSION['admin_id']))
                echo "<div class='header warning'>Failed to remove admin</div>";
            }
        //   ASSERT require_privs(tid, aid)
        //      and $_POST[tournament_id] is set to tid
        elseif ($_POST['action'] == 'import_teams') { teams_import($_POST, $_SESSION['admin_id']) || die("import failed"); }
        elseif ($_POST['action'] == 'add_team')     { team_add($_POST);     }
        elseif ($_POST['action'] == 'disable_team') { team_disable($_POST); }
        elseif ($_POST['action'] == 'delete_team')  { team_delete($_POST); }
        elseif ($_POST['action'] == 'update_team')  { team_update($_POST);  }
        if ($redir) header("location:main_menu.php");  // stick this back in delete_tournament?
    }
?>
<div class="con">
    <div class="centerBox">
        <?  //disp_status($tourney) ?>
        <div class="mainBox">
            <div class="header">Rounds</div>
            <?
                $t_rounds = get_tournament_modules($tid);
                disp_modules($t_rounds);
            ?>
            <form name="modules" method="post" action="">
                <input type='hidden' name='action' value='new_module' />
                <div class='line'><?php disp_tournament_button("Add Round", 'new_module'); ?></div>
            </form>
        </div>

        <div class="mainBox">
            <div class="header">Details</div>
            <form name="details" method="post" action="">
                <input type='hidden' name='action' value='update_tournament' />
                <?php disp_tournament_details($tourney); ?>
                <div class='line'><?php disp_tournament_button("Save Details", 'update_tournament'); ?></div>
            </form>
        </div>

        <div class="mainBox">
            <div class="header">Admins</div>
                <form name='admin' method='post' action=''>
                    <input type='hidden' name='action' value='add_admin' />
                    <?php disp_admins($tourney, $_SESSION['admin_id']); ?>
                    <?php
                    if ($tourney['tournament_owner'] == $_SESSION['admin_id']) {
                        echo "<p> ";
                        disp_tournament_button("Add", "add_admin");
                        echo "<input type='text' name='admin_email' value='admin email'/> </p>\n";
                    }
                    ?>
                </form>
            <div class='header'></div>
        </div>

        <div class="mainBox">
            <div id='teams' class="header">Teams</div>
            <form name='admin' method='post' action='#teams'>
                <input type='hidden' name='team_id' value=''>
                <input type='hidden' name='action' value='' />
                <table>
                    <tr><th></th><th>Team Name</th><th>Players</th>
                        <th title="if unsure, leave blank"><i>UID</i></th>
                        <th title="for random starting rank, leave blank">Starting Rank</th></tr>
                    <tr> <td> <? disp_tournament_button("Add", "add_team"); ?> </td>
                        <td><input type='text' name='name_add'></td>
                        <td><input type='text' name='text_add'></td>
                        <td><input class='numeric' type='text' name='uid_add'></td>
                        <td><input class='numeric' type='text' name='init_add'></td>
                    </tr>
                    <tr style='height:20px;'><th colspan=7>&nbsp;</th></tr>
                    <?php
                    foreach (get_tournament_teams($tid, "team_id") as $team)
                        disp_team_edit($team);
                    ?>
                </table>
            </form>
            <div class='header'></div>
        </div>
        <div class="mainBox">
            <div class="header">Import Teams</div>
            <form name="import" method="post" action="">
                Import top <input class='numeric' type='text' name='imp_num' />
                teams from <select name='imp_tid'>
                <?php
                    $tournaments = get_my_tournaments($_SESSION['admin_id']);
                    foreach ($tournaments as $t)
                        if ($t['tournament_id'] != $tid)
                            echo "<option value='{$t['tournament_id']}'>{$t['tournament_name']}</option>";
                ?>
                </select>

                <br>

                Import teams from web URL <input type='text' name='imp_url' >

                <input type='hidden' name='action' value='import_teams' />
                <p><input class='button' type='submit' name='submit' value='Import' /></p>
           </form>
        </div>
        <div class="nav rHead">
            <form id="delForm" name='delete' method='post' action=''>
                <input type='hidden' name='action' value='delete_tournament' />
                <?php 
                    if (tournament_isowner($tourney['tournament_id'], $_SESSION['admin_id']))
                        echo '<a href="#" onClick="delTournament()">Delete Tournament</a>';
                        //disp_tournament_button('Delete Tournament', 'delete_tournament');
                ?>
            </form>
            <div class='header'></div>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
