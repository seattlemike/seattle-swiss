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

    if (isset($_GET['all']))
        $title_text = "All Tournaments";
    else
        $title_text = "Recent Tournaments";

    $page_name = "Standings";
    include("header.php");
?>

<div class='con'>
    <div class='centerBox'>
        <?php 
            ($tid  = $_GET['id'])   || ($tid = $_POST['tournament_id']);

            if ($tid) {
                if ((tournament_ispublic($tid)) || 
                    (check_login() && tournament_isadmin($tid, $_SESSION['admin_id'])))
                    disp_standings($tid, $_GET['view']);
                else
                    header("location:view.php");
            }
            else {  // List of public tournaments
                $tlist = sql_select_all("SELECT * FROM tblTournament WHERE is_public = 1 ORDER BY tournament_date DESC", array());
                // only list tournaments from the past year?
                if (! isset($_GET['all'])) {
                    foreach( $tlist as $k => $t ) {
                        if ((strtotime($t['tournament_date']) > time() +3600 * 24 * 7))
                            //(strtotime($t['tournament_date']) < time() -3600*24*365))
                            unset($tlist[$k]);
                    }
                    disp_tournaments(array_slice($tlist,0,10), 'view.php');
                    echo '<div class="nav line"> <a href="view.php?all">Older Tournaments</a> </div>';
                } else {
                    disp_tournaments($tlist, 'view.php');
                }
            }
        ?>
    </div>
</div>
<? include("footer.php"); ?>
