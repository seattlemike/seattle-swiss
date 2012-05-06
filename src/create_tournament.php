<?php
    $title_text = "Create Tournament";
	include("header.php");
	require_login();

    if ($_POST['action'] == 'new_tournament') {
        if (create_tournament($_POST, $_SESSION['admin_id']))
            header("location:main_menu.php");
        else die("Failed to create new tournament");
    }
?>

<div class='con'>
<div class='centerBox'>
    <div class='mainBox'>
        <form name='tournament' method='post' action=''>
            <input type='hidden' name='action' value='' />
            <label>Name<input class='wide' type='text' maxlength='40' name='tournament_name' value='' /></label>
            <label>Date<input class='wide' type='text' maxlength='40' name='tournament_date' value='<? echo date("m/d/Y");?>' /></label>
            <label>Mode<select name='tournament_mode'>
                <option value='0'>Swiss Rounds</option>";
                <option value='1'>Single Elimination</option>";
                <option value='2'>Double Elimination</option>";
            </select></label>
            <? disp_tournament_button("Create", 'new_tournament'); ?>
        </form>
    </div>
</div>
</div>

<? include("footer.php"); ?>
