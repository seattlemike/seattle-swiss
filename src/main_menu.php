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
    include("header.php");
    require_login();

    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'new_tournament') {
            if ($new_id = new_tournament())
                header("location:/private/tournament/$new_id/");
        }
    }

    disp_header("Tournaments");
    disp_topbar();
    disp_titlebar();

?>

<div class="con">
    <div class="centerBox">
        <div class='mainBox'>
            <div class='line'>
            <form name="new_tourney" method="post" action="">
                <input type='hidden' name='action' value='new_tournament' />
                <input class='button' type='submit' name='submit' value='New Tournament' />
            </form>
            </div>
        </div>
        <div class='mainBox'>
            <div class="header">Your Tournaments</div>
            <?php
            if (isset($_GET['super'])) {
                require_privs(false);  // dies unless $_SESSION['admin_type']=='super'
                // should super get un-fixed tournaments? -- "WHERE is_fixed=1" if no
                $tlist = sql_select_all( "SELECT * FROM tblTournament ORDER BY tournament_date DESC", array() );
            }
            else
                $tlist = get_my_tournaments();
            disp_tournaments($tlist);
            ?>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
