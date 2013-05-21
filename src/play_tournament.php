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

    // check for round
    ($rid = $_POST['round_id']) || ($rid = $_GET['round']);
    $round = get_round($rid);
    if ($round)
        $mid = $round['module_id'];
    else
        ($mid = $_POST['module_id']) || ($mid = $_GET['module']);

    // Ensure mid/tid are valid and that we have privs to edit
    $module = get_module($mid) ;
    if (! $module) 
        header_redirect("main_menu.php");
    $tid = $module['parent_id'];
	require_login();
    require_privs( tournament_isadmin($tid, $_SESSION['admin_id']) );
    $tourney = get_tournament($tid);
    if (! $round) {
        ($round = get_current_round($mid)) 
        && ($rid = $round['round_id']);
    }

    $js_extra = array("ui.js", "async.js", "run_module.js");
    $header_extra = array( '<script type="text/javascript">window.onload = runModuleOnLoad</script>' );
    disp_header($module['module_title']." : Run", $js_extra, $header_extra);
    disp_topbar($tourney, $module, 2);
    disp_titlebar($module['module_title']);

    if (isset($_POST['case'])) {
        // make sure post data matches what we've validated
        $_POST['tournament_id'] = $tid;
        $_POST['module_id'] = $mid;

        switch ($_POST['case']) {
            case 'start_module':
                $rounds = get_module_rounds($mid);
                if ($rounds)
                    $round_id = $rounds[count($rounds)-1]['round_id'];
                else 
                    $round_id = module_new_round($mid);
                header_redirect("play_tournament.php?round=$round_id");
                break;
            case 'add_round':
                if ($rid=tournament_add_round($tid))
                    header("location:play_tournament.php?id=$tid&round_id=$rid");
                break;
            case 'update_score':
                // MIKE TODO IMMEDIATE validate:  $game_id is a member of tournament $tid
                tournament_update_score($_POST['game_id'], $_POST);
                break;
            case 'delete_round':
                if ($round && round_delete($round))
                    header_redirect("play_tournament.php?module=$mid");
                die("Failed to delete round");
            case 'empty_round':
                round_empty($rid);
                break;
            case 'populate_round':
                round_populate($rid);
                break;
            case 'add_game':
                if (module_hasteam($mid, $_POST['team_a']) && module_hasteam($mid, $_POST['team_b']))
                    round_add_game($rid, array( array("id" => $_POST['team_b']), array( "id" => $_POST['team_a']) ));
                break;
            case 'delete_game':
                if ($round && game_in_round($rid, $_POST['game_id']))
                    game_delete($_POST['game_id']);
                break;
        }
    }

    // adds a round id to the url if rounds exist
    $url = "play_tournament.php?" . $round ? "round=$rid" : "module=$mid";
?>
<div class="con">
    <div class="centerBox"> 
        <?php
        if (! count(get_tournament_teams($tid))) {
            echo "<div class='mainBox'><div class='header'>Tournament has no teams yet</div>";
            echo "<a class='button' href='tournament.php?id=$tid'>Back</a></div>\n";
        } elseif (! count(get_module_teams($mid))) {
            echo "<div class='mainBox'><div class='header'>Round has no active teams yet</div>";
            echo "<a class='button' href='module.php?module=$mid'>Back</a></div>\n";
        } else {
        ?>
        <div class='mainBox'>
            <form name='rounds' action='' method='post'>
                <input type='hidden' name='case' value='start_module' />
                <input type='hidden' name='module_id' value='<?echo $mid?>' />
                <input type='hidden' name='round_id' value='<?echo $rid?>' />
                <? disp_round_nav($mid, $round, true);  ?>
            </form>

            <? if ($round) {
                echo "<div id='games'>";
                disp_round_games($round);
                //disp_round_games($_POST['toggles'], $tid, $rid, $_SESSION['admin_id']); 
                echo "</div>";
            } ?>
        </div>
            
            <? if ($round) { ?>
            <div class="mainBox">
                <div class="header toggle" id="edit_round" onclick="toggleHide('edit-round-controls')">Edit Round</div> 
                    <div id="edit-round-controls" style="display: none;">
                        <form name='populate_tournament' action='' method='post'>
                            <input type='hidden' name='case' value='edit_round' />
                            <input type='hidden' name='round_id' value='<? echo $rid ?>' />
                            <div class="line">
                                <?php
                            //    <a id="fill-btn" class="button">Fill Round</a>
                            //    <a id="empty-btn" class="button">Empty Round</a>
                            //    <a id="delete-btn" class="button">Delete Round</a>
                                disp_tournament_button('Fill Round', 'populate_round');
                                disp_tournament_button('Empty Round', 'empty_round');
                                disp_tournament_button('Delete Round', 'delete_round');
                                ?>
                            </div>
                            <div class="line">
                                <?
                                disp_tournament_button('Add Game', 'add_game');
                                disp_team_select($mid, 'team_a');
                                disp_team_select($mid, 'team_b');
                                ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
<?php
            }
        }
?>

    </div>
</div>
<?php include("footer.php"); ?>
