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

    $title_text = "Login";
	include("header.php"); 
    disp_header("Login");
    disp_topbar();
    disp_titlebar();
		
  if ($_POST['action'] == 'login') {
		if (login($_POST['email'], $_POST['password']))
			header("location:main_menu.php");
		else 
			echo "<div class='header warning'>Wrong Email or Password</div>";
  }
  elseif ($_POST['action'] == 'create') {
    // on success, note about 'click link in verification email to enable account'
    list($status, $msg) = admin_create($_POST);
		if ($status)
			echo "<div class='header warning'>Admin ".$_POST['name']." successfully created.</div>";
		else
			echo "<div class='header warning'>$msg</div>";
	}

?>
<div class="con">
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
