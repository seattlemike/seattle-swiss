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
