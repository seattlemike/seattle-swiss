<?php
    $title_text = "Create Tournament";
	include("header.php");
	require_login();

  if ($_POST['action'] == 'new_tournament') {
    if (tournament_create($_POST, $_SESSION['admin_id']))
      header("location:main_menu.php");
    else die("Failed to create new tournament");
  }
?>

<div class='con'>
<div class='centerBox'>
    <div class='mainBox'>
        <form name='tournament' method='post' action=''>
            <input type='hidden' name='action' value='' />
            <table> <?  disp_tournament_details(); ?> </table>
            <? disp_tournament_button("Create", 'new_tournament'); ?>
        </form>
    </div>
</div>
</div>

<? include("footer.php"); ?>
