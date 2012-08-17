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
