<?php
    $page_name="Edit";
	include("header.php");  // sets $tid if valid tournament

    if (! $tid) header("location:main_menu.php");
	require_login();
    require_privs( tournament_isadmin($tid, $_SESSION['admin_id']) );

    //TODO: validate tournament details
    if (isset($_POST['action'])) {
        $_POST['tournament_id'] = $tid;

        if ($_POST['action'] == 'delete_tournament') {
            if ($_POST['confirmed'] == 'true') {
                $redir = tournament_delete($_POST, $_SESSION['admin_id']);
                $redir || die("Failed tournament delete!");
            } else { $confirm='true'; }
        }
        elseif ($_POST['action'] == 'update_tournament')
            tournament_edit($_POST, $_SESSION['admin_id']);
        elseif ($_POST['action'] == 'add_admin') {
            if (! tournament_add_admin($_POST, $_SESSION['admin_id']))
                echo "<div class='header warning'>Failed to add admin</div>";
            }
        elseif ($_POST['action'] == 'remove_admin') {
            if (! tournament_remove_admin($_POST, $_SESSION['admin_id']))
                echo "<div class='header warning'>Failed to remove admin</div>";
            }
        elseif ($_POST['action'] == 'add_team') {
            team_add($_POST, $_SESSION['admin_id']);
        }
        elseif ($_POST['action'] == 'disable_team') {
            team_disable($_POST, $_SESSION['admin_id']);
        }
        elseif ($_POST['action'] == 'update_team') {
            team_edit($_POST, $_SESSION['admin_id']);
        }
        if ($redir) header("location:main_menu.php");
    }
  ?>

<?php 
    $tourney = get_tournament($tid, $_SESSION['admin_id']);
    
    if ($confirm == 'true') {  //want a better way to do this.  Javascript, I suppose.
        echo "<div class='header warning'><h2>Are you sure you want to delete this tournament?</h2></div>"; 
    }
?>
<div class="con">
    <div class="centerBox">
        <? disp_status($tourney) ?>
        <div class="mainBox">
            <div class="header">Details</div>
            <form name="details" method="post" action="">
                <input type='hidden' name='action' value='' />
                <?php disp_tournament_details($tourney); ?>
                <div class='line'><?php disp_tournament_button("Save Details", 'update_tournament'); ?></div>
            </form>
        </div>

        <div class="mainBox">
            <div class="header">Admins</div>
                <form name='admin' method='post' action=''>
                    <input type='hidden' name='action' value='' />
                    <?php disp_admins($tourney, $_SESSION['admin_id']); ?>
                    <?php
                    if ($tourney['tournament_owner'] == $_SESSION['admin_id']) {
                        echo "<p> ";
                        disp_tournament_button("Add", "add_admin");
                        echo "<input type='text' name='admin_email' value='admin email'/> </p>\n";
                    }
                    ?>
                </form>
        </div>

        <div class="mainBox">
            <div class="header">Teams</div>
            <form name='admin' method='post' action=''>
                <input type='hidden' name='team_id' value=''>
                <input type='hidden' name='action' value='' />
                <table>
                    <tr><th></th><th>Team Name</th><th>Players</th>
                        <th title="if unsure, leave blank"><i>UID</i></th>
                        <th title="for random starting rank, leave blank">Starting Rank</th></tr>
                    <tr> <td> <? disp_tournament_button("Add", "add_team"); ?> </td>
                        <td><input type='text' name='add_name'></td>
                        <td><input type='text' name='add_text'></td>
                        <td><input class='numeric' type='text' name='add_uid'></td>
                        <td><input class='numeric' type='text' name='add_starting_rank'></td>
                    </tr>
                    <?php
                        foreach (get_tournament_teams($tourney['tournament_id']) as $team)
                            disp_team_edit($team); 
                    ?>
                    <tr><td colspan=5 class='center'>
                        <? disp_tournament_button("Update", "update", "", "selected"); ?></div>
                    </td></tr>
                </table>
            </form>
        </div>
        <div class="nav rHead">
            <form name='delete' method='post' action=''>
                <input type='hidden' name='action' value='' />
                <input type='hidden' name='confirmed' value='<? echo $confirm ?>' />
                <?php 
                    if (tournament_isowner($tourney['tournament_id'], $_SESSION['admin_id']))
                    if ($confirm)
                        disp_tournament_button('Yes, Delete Tournament', 'delete_tournament');
                    else 
                        disp_tournament_button('Delete Tournament', 'delete_tournament');
                ?>
            </form>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
