<?php
    $title_text ="User Settings";
	include("header.php");  // sets $tid if valid tournament
	require_login();

    if (isset($_POST['action'])) {
        if ($redir) header("location:main_menu.php");
    }
  ?>

<div class="con">
    <div class="centerBox">
        <div class="mainBox">
            <div class="header"><? echo $_SESSION['admin_name']; ?></div>
            <p>Oops.  Not yet finished.</p>
            <p>Coming soon:  change password, change username / location, and more!
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
