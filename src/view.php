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
    // TODO: this is just a duplicate of the module-view component of public.php
    //       where we should just work a public/private/link-only check in and use that instead

    include("header.php");

    // Ensure mid/tid are valid and that we have privs to edit
    ($mid = $_POST['module_id']) || ($mid = $_GET['module']);
    try {
        $module = get_module($mid) ;
        if ($module)
            $tid = $module['parent_id'];
        else 
            ($tid = $_POST['tournament_id']) || ($tid = $_GET['id']);
        $tourney = get_tournament($tid);
        require_login();
        require_privs( tournament_isadmin($tid) );
    } catch ( Exception $e) {
        header_redirect("/private/");
    }

    $title = $module['module_name'];
    $js_extra = array("/ui.js", "/view_ui.js");
    $header_extra = array( '<script type="text/javascript">window.onload = viewOnLoad</script>' );
    disp_header($title, $js_extra, $header_extra);
    disp_topbar($tourney, $module, 3);
    disp_titlebar($title);
    echo "<div class='con'> <div class='centerBox'> <div class='mainBox'>\n";
    if ($module) {
        if (count(get_module_rounds($module['module_id']))) {
            $default_view = array("standings", "bracket", "wbracket"); // default by module_mode if no $_GET['view']
            disp_standings($module, $_GET['view'] ? $_GET['view'] : $default_view[$module['module_mode']]);
        } else {
            echo "<div class='header'>Not yet started</div><div class='line'>";
            disp_teams_list(get_module_teams($mid));
            echo "</div>";
        }
    }
    echo "</div></div></div>\n";
    include("footer.php"); 
?>
