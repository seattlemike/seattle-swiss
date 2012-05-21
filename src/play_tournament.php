<?php
    $page_name = "Run";
    include("header.php");

	// require tournament & on its admin list
	$tid     || header("location:main_menu.php");

    if ($rid == -1) unset($rid);  // set in header if round not found with rid

    
    // TODO this doesn't look nice.  how can we make this look nicer?
    // check POST actions
    if (isset($_POST['action'])) {
        require_login();
        require_privs( tournament_isadmin($tid, $_SESSION['admin_id']) );

        switch ($_POST['action']) {
            case 'add_round':
                if ($rid=tournament_add_round($tid))
                    header("location:play_tournament.php?id=$tid&round_id=$rid");
                break;
            case 'update_score':
                //TODO validate:  $game_id is a member of tournament $tid
                tournament_update_score($_POST['game_id'], $_POST);
                break;
            case 'remove_round':
                // TODO validate: make sure $rid in $tid
                if (tournament_delete_round($rid, $_SESSION['admin_id']))
                    header("location:play_tournament.php?id=$tid");
                break;
            case 'empty_round':
                // TODO validate: make sure $rid in $tid
                tournament_empty_round($rid, $_SESSION['admin_id']);
                break;
            case 'populate_round':
                //URGENT
                //MIKE TODO IMMEDIATE: only "next round" if doesn't already exist
                $pop_rid = tournament_populate_round($tid, $_POST['populate_id'], $_SESSION['admin_id']);
                if ($pop_rid != $rid)
                    header("location:play_tournament.php?id=$tid&round_id=$pop_rid");
                break;
            case 'add_game':
                // TODO validate: make sure team_id is a member of tournament
                tournament_add_game($rid, array( array("id" => $_POST['team_a']), array( "id" => $_POST['team_b']) ));
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

  //TODO:  Tournament Structure!
  // ASSERT:  NO PRIVS CHECKED YET
// sets the view/edit mode based on the admin's privileges
if (check_login() && tournament_isadmin($tid, $_SESSION['admin_id']))
    $mode = 'edit';
elseif (tournament_ispublic($tid))
    $mode = 'view';
else
	header("location:view.php");

// adds a round id to the url if rounds exist
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
