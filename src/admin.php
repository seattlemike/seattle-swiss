<?php 
    $title_text = "Login";
	include("header.php"); 
		
  if ($_POST['action'] == 'login') {
		if (login($_POST['email'], $_POST['password']))
			header("location:main_menu.php");
		else 
			echo "<div class='header warning'>Wrong Email or Password</div>";
  }
  elseif ($_POST['action'] == 'create') {
    list($status, $msg) = create_admin( $db, $_POST['email'], $_POST['name'], $_POST['location'], $_POST['password'] );
		if ($status)
			echo "<div class='header warning'>Admin ".$_POST['name']." successfully created.</div>";
		else
			echo "<div class='header warning'>$msg</div>";
	}

?>
<div class="con">
    <? // TODO: BIG NOTICE if we're currently running the tournament ?>
    <div class="centerBox">
        <div class="mainBox">
            <div class='header'>Admin Login</div>
            <form name="login.form" method="post" action="admin.php">
                <input type="hidden" name="action" value="login">
                <label>Email    <input name="email" type="text" id="email" maxlength="40" /> </label>
                <label>Password <input name="password" type="password" id="password" maxlength="40" /> </label>
                <input class="button" type="submit" name="submit" value="Login" />
            </form>
        <div class='header'></div>
        </div>
        <div class="mainBox">
            <div class='header'>Create New Account</div>
            <form name="newadmin.form" method="post" action="admin.php">
                <input type="hidden" name="action" value="create">
                <label>Name     <input name="name" type="text" id="rname" maxlength="40" /></label>
                <label>Email    <input name="email" type="text" id="email" maxlength="40" /></label>
                <label>Location <input name="location" type="text" id="location" maxlength="40" /></label>
                <label>Password <input name="password" type="password" id="password" maxlength="40" /></label>
                <input class="button" type="submit" name="submit" value="Create Account" />
            </form>
        <div class='header'></div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
