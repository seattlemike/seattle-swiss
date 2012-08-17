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
        <div class="nav line">
            <a class="button" href="create_tournament.php">New Tournament</a>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
