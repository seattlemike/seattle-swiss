<?php
  $title_text = "Tournaments";

  include("header.php");
  require_login();

  if (isset($_POST['action'])) {
    if ($_POST['action'] == 'edit_tournament')
      header('location:tournament.php');
    elseif ($_POST['action'] == 'play_tournament')
      header('location:play_tournament.php');
  }

?>

<div class="con">
    <div class="centerBox">
        <div class="mainBox">
            <?php
            if (isset($_GET['super'])) {
                require_privs(false);
                $tlist = sql_select_all( "SELECT * FROM tblTournament ORDER BY tournament_date DESC", array() );
            }
            else
                $tlist = get_my_tournaments($_SESSION['admin_id']);

            if ($tlist) 
                disp_tournaments($tlist);
            ?>
        </div>
        <div class="nav line">
            <a class="button" href="create_tournament.php">New Tournament</a>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
