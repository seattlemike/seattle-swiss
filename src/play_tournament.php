<?php
    $page_name = "Run";
    include("header.php");

	// require tournament & on its admin list
	$tid     || header("location:main_menu.php");

    if ($rid == -1) unset($rid);  // set in header if round not found with rid

    // check POST actions
    //    TODO this doesn't look nice.  how can we make this look nicer?

    if (isset($_POST['action'])) {
        require_login();
        require_privs( tournament_isadmin($tid, $_SESSION['admin_id']) );

        if ($_POST['action'] == 'add_round') {
            if ($rid=tournament_add_round($tid))
                header("location:play_tournament.php?id=$tid&round_id=$rid");
        } 
        elseif ($_POST['action'] == 'update_score') {
            tournament_update_score($_POST['game_id'], $_POST);
        } 
        elseif ($_POST['action'] == 'remove_round') {
            if (tournament_delete_round($rid, $_SESSION['admin_id']))
                header("location:play_tournament.php?id=$tid");
        }
        elseif ($_POST['action'] == 'empty_round') {
            tournament_empty_round($rid, $_SESSION['admin_id']);
        }
        elseif ($_POST['action'] == 'populate_round') {
            $pop_rid = tournament_populate_round($tid, $_POST['populate_id'], $_SESSION['admin_id']);
            if ($pop_rid != $rid)
                header("location:play_tournament.php?id=$tid&round_id=$pop_rid");
        }
        elseif ($_POST['action'] == 'add_game') {
            tournament_add_game($rid, array( array("id" => $_POST['team_a']), array( "id" => $_POST['team_b']) ));
        }
        elseif ($_POST['action'] == 'toggle_oncourt') {
            tournament_toggle_court($_POST['game_id']);
        }
        elseif ($_POST['action'] == 'delete_game') {
            tournament_delete_game($_POST['game_id']);
        }
    }

  //TODO:  Tournament Structure!
  // ASSERT:  NO PRIVS CHECKED YET

if (check_login() && tournament_isadmin($tid, $_SESSION['admin_id']))
    $mode = 'edit';
elseif (tournament_ispublic($tid))
    $mode = 'view';
else
	header("location:view.php");


$url = "play_tournament.php?id=$tid";
if (isset($rid)) $url .= "&round_id=$rid";

?>
<div class="con">
    <div class="centerBox"> 
        <div class='mainBox'>
            <div class="header">Rounds</div>
            <div class="line nav">
                <form name='play_tournament' action='<?echo $url;?>' method='post'>
                    <input type='hidden' name='action' value=''>
                    <input type='hidden' name='populate_id' value=''>
                    <div class="rHead">
                        <?php
                        if (($mode == 'edit') && isset($rid) && tournament_round_is_done($rid))
                            disp_next_round_button($tid, $rid); 
                        ?>
                    </div>
                    <div class="lHead">
                        <? disp_round_nav($tid, $rid, $_SESSION['admin_id']); ?>
                    </div>
                </form>
            </div>
            <? if (isset($rid)) { 
                echo "<div id='games'>";
                disp_games($_POST['toggles'], $tid, $rid, $_SESSION['admin_id']); 
                echo "</div>";
            } ?>
        </div>
            
        <?php if (isset($rid) && ($mode == 'edit')) { ?>
            <div class="mainBox">
                <div class="header">Edit Round</div> 
                <form name='populate_tournament' action='<? echo $url?>' method='post'>
                    <input type='hidden' name='action' value=''>
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
        <? } 
        ?>
    </div>
    </div>
</div>
<?php include("footer.php"); ?>
