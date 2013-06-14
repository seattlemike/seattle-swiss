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

    $js_extra = array("/ui.js");
    disp_header($tourney['tournament_name'], $js_extra);
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
            case 'import_teams': 
                //teams_import($_POST) || die("import failed"); 
                break;
            case 'team_add': 
                team_add($_POST);
                break;
            case 'disable_team':
                team_disable($_POST);
                break;
            case 'delete_team':  
                team_delete($_POST);
                break;
            case 'update_team':
                team_update($_POST);
                break;
        }
        if ($redir) header("location:/private/");
    }
?>
<div class="con">
    <div class="centerBox">
        <?  //disp_status($tourney) ?>
        <div class="mainBox modules">
            <div class="header">Rounds</div>
            <?
                $t_rounds = get_tournament_modules($tid);
                disp_modules($t_rounds);
            ?>
            <form name="modules" method="post" action="">
                <input type='hidden' name='case' value='new_module' />
                <div class='line'><input class='button' type='submit' name='add_round' value="Add Round"></div>
            </form>
        </div>

        <div class="mainBox">
            <div class="header">Details</div>
            <form name="details" method="post" action="">
                <input type='hidden' name='case' value='update_tournament' />
                <?php disp_tournament_details($tourney); ?>
                <div class='line'><input class='button' type='submit' name='save' value="Save Details"></div>
            </form>
        </div>

        <div class="mainBox">
            <div class="header">Admins</div>
                <form name='admin' method='post' action=''>
                    <input type='hidden' name='case' value='add_admin' />
                    <?php disp_admins($tourney, $_SESSION['admin_id']); ?>
                    <?php
                    if ($tourney['tournament_owner'] == $_SESSION['admin_id']) {
                        echo "<div class='line'><input class='button' type='submit' name='add_admin' value='Add'>";
                        echo "<input type='text' name='admin_email' value='admin email'/> </div>\n";
                    }
                    ?>
                </form>
            <div class='header'></div>
        </div>

        <div class="mainBox">
            <div id='teams' class="header">Teams</div>
            <form name='admin' method='post' action='#teams'>
                <input type='hidden' name='team_id' value=''>
                <input type='hidden' name='case' value='team_add' />
                <table>
                    <tr><th></th><th>Name</th><th>Note</th>
                        <th title="<? echo "Unique ID for external stat-keeping.\nIf unsure, leave blank";?>"><i>UID</i></th></tr>
                    <tr><td><input class='button' type='submit' name='team_add' value="Add"></td>
                        <td><input type='text' name='name_add'></td>
                        <td><input type='text' name='text_add'></td>
                        <td><input class='numeric' type='text' name='uid_add'></td>
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
                <?php // IMPORT TEAMS
                    //$tournaments = get_my_tournaments($_SESSION['admin_id']);
                    //foreach ($tournaments as $t)
                    //    if ($t['tournament_id'] != $tid)
                    //        echo "<option value='{$t['tournament_id']}'>{$t['tournament_name']}</option>";
                ?>
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
    </div>
</div>
<?php include("footer.php"); ?>
