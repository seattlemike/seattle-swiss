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

    $page_name = "Run";
    include("header.php");

	// require tournament & admin
	$tid     || header("location:main_menu.php");
    require_login();
    require_privs( tournament_isadmin($tid, $_SESSION['admin_id']) );

    if ($rid == -1) unset($rid);  // set in header if round not found with rid
    
    // TODO this doesn't look nice.  how can we make this look nicer?
    // check POST actions
    if (isset($_POST['action'])) {
        $_POST['tournament_id'] = $tid;  // sanitizing $_POST['tournament_id']

        switch ($_POST['action']) {
            case 'add_round':
                if ($rid=tournament_add_round($tid))
                    header("location:play_tournament.php?id=$tid&round_id=$rid");
                break;
            case 'update_score':
                // MIKE TODO IMMEDIATE validate:  $game_id is a member of tournament $tid
                tournament_update_score($_POST['game_id'], $_POST);
                break;
            case 'remove_round':
                // MIKE TODO IMMEDIATE validate: make sure $rid in $tid
                if (tournament_delete_round($rid, $_SESSION['admin_id']))
                    header("location:play_tournament.php?id=$tid");
                break;
            case 'empty_round':
                // MIKE TODO IMMEDIATE validate: make sure $rid in $tid
                tournament_empty_round($rid, $_SESSION['admin_id']);
                break;
            case 'populate_round':
                //MIKE TODO IMMEDIATE: only "next round" if doesn't already exist
                $pop_rid = tournament_populate_round($tid, $_POST['populate_id'], $_SESSION['admin_id']);
                if ($pop_rid != $rid)
                    header("location:play_tournament.php?id=$tid&round_id=$pop_rid");
                break;
            case 'add_game':
                // TODO validate: make sure team_id is a member of tournament
                tournament_add_game($rid, array( array("id" => $_POST['team_b']), array( "id" => $_POST['team_a']) ));
                break;
            case 'toggle_oncourt':
                // TODO validate: make sure game_id is a member of tournament
                tournament_toggle_court($_POST['game_id']);
                break;
            case 'delete_game':
                // TODO validate: make sure game_id is a member of tournament
                tournament_delete_game($_POST['game_id']);
                break;
        }
    }

    // adds a round id to the url if rounds exist
    $url = "play_tournament.php?id=$tid";
    if (isset($rid)) $url .= "&round_id=$rid";

?>
<div class="con">
    <div class="centerBox"> 
        <div class='mainBox'>
            <div class="header">Rounds</div>
            <div class="nav">
                <form name='play_tournament' action='<?echo $url;?>' method='post'>
                    <input type='hidden' name='action' value=''>
                    <input type='hidden' name='populate_id' value=''>
                    <div class="lHead">
                        <?
                            disp_round_nav($tid, $rid, true); 
                        ?>
                    </div>
                </form>
            </div>
            <? if (isset($rid)) { 
                echo "<div id='games'>";
                disp_games($_POST['toggles'], $tid, $rid, $_SESSION['admin_id']); 
                echo "</div>";
            } ?>
        </div>
            
            <? if ($rid) { ?>
            <div class="mainBox">
                <div class="header" id="edit_round" onclick="toggleHide('edit-round-controls')">Edit Round Controls</div> 
                    <div id="edit-round-controls" style="display: none;">
                        <form name='populate_tournament' action='<? echo "$url#edit_round"?>' method='post'>
                            <input type='hidden' name='action' value='#edit_round'>
                            <input type='hidden' name='populate_id' value=''>
                            <div class="line">
                                <?php
                                disp_tournament_button("Fill", "populate_round", " this.form.elements[\"populate_id\"].value=\"$rid\";");
                                disp_tournament_button("Empty", "empty_round");
                                disp_tournament_button("Delete", "remove_round");
                                ?>
                            </div>
                            <div class="line">
                                <?php
                                disp_tournament_button('Add Game', 'add_game');
                                disp_team_select($tid, 'team_a');
                                disp_team_select($tid, 'team_b');
                                ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <? } ?>
    </div>
</div>
<?php include("footer.php"); ?>
