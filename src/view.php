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

    // TODO: move this to whatever our public view tournaments file becomes
    //if (isset($_GET['all'])) $title_text = "All Tournaments";
    //else $title_text = "Recent Tournaments";

    // Ensure mid/tid are valid and that we have privs to edit
    ($mid = $_POST['module_id']) || ($mid = $_GET['module']);
    $module = get_module($mid) ;
    if ($module)
        $tid = $module['parent_id'];
    else 
        ($tid = $_POST['tournament_id']) || ($tid = $_GET['id']);
    $tourney = get_tournament($tid);

	require_login();
    require_privs( tournament_isadmin($tid) );

    if ($module)
        $title = $module['module_title'];
    else
        $title = $tourney['tournament_name'];

    disp_header($title);
    disp_topbar($tourney, $module, 3);
    disp_titlebar($title);
    //} else {
    //    disp_header($tourney['tournament_name']);
    //    disp_topbar($tourney);
    //    disp_titlebar($tourney['tournament_name']);
    //}

        // default view if nothing explicitly chosen
        echo "<div class='con'> <div class='centerBox'> <div class='mainBox'>\n";
        if ($module) {
            if (count(get_module_rounds($module['module_id']))) {
                $default_view = array("results", "bracket", "wbracket"); // default by module_mode if no $_GET['view']
                disp_standings($module, $_GET['view'] ? $_GET['view'] : $default_view[$module['module_mode']]);
            } else {
                echo "<div class='header'>Not yet started</div>";
                disp_teams_list(get_module_teams($mid));
            }
        } elseif ($tourney) {
            if ($tourney['tournament_privacy'] > 0)
                disp_modules_list(get_tournament_modules($tid), "view.php");
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
    echo "</div></div></div>\n";
    include("footer.php"); 
?>
