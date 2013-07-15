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

	include("header.php");  // sets $tid if valid tournament
    $tid = get_tid();
    if (! $tid) 
        header_redirect("location:/private/");
    $_POST['tournament_id'] = $tid;

	require_login();
    require_privs( tournament_isadmin($tid) );
    $tourney = get_tournament($tid);

    $js_extra = array("/ui.js", "/tournament_ui.js", "/async.js");
    $header_extra = array( '<script type="text/javascript">window.onload = tournamentOnLoad</script>' );
    disp_header($tourney['tournament_name'], $js_extra, $header_extra);
    disp_topbar($tourney, null, 0);
    disp_titlebar($tourney['tournament_name']);
?>
<div class="con">
    <div class="centerBox">
        <?  //disp_status($tourney) ?>

        <?php
        $isowner = ( $tourney['tournament_owner'] == $_SESSION['admin_id'] );
        echo "<div class='mainBox' id='details' data-tid='$tid' data-owner='$isowner'>";
            echo "<div class='header'>Tournament Details</div>";
            echo "<div class='item'>".date("M d, Y", strtotime($tourney['tournament_date']))."</div>";
            echo "<div class='item'>{$tourney['tournament_name']}</div>";
            echo "<div class='item'>{$tourney['tournament_text']}</div>";
            echo "<div class='item'><a href='http://codex.wordpress.org/Glossary#Slug'>Slug</a>: <i>{$tourney['tournament_slug']}</i></div>";
            $privacy = array("Private", "Anyone-with-link", "Public");
            echo "<div class='item' data-priv='{$tourney['tournament_privacy']}'>Privacy: <i>{$privacy[$tourney['tournament_privacy']]}</i></div>";
            ?>
            <a class='button' id='edit-details'>Edit</a>
        </div>
        <div id="admins" class="mainBox">
            <div class="header">Tournament Admins</div>
            <div id='admins-list'>
            <?  disp_admins_list($tourney); ?>
            </div>
            <a class='button' id='add-admin'>Add</a>
            <a class='button' id='remove-admin'>Remove</a>
        </div>
        <div id="modules" class="mainBox">
            <div class="header">Tournament Modules</div>
            <?php
                echo "<div id='modules-list'>";
                $t_modules = get_tournament_modules($tid);
                if (! $t_modules)
                    echo "<p>No modules scheduled yet</p>";
                disp_modules_list($t_modules);
                echo "</div>";
            ?>
            <a class='button' id='add-module'>Add Module</a>
        </div>
        <div class="mainBox" id="teams">
            <div class="header">Tournament Teams</div>
            <div class='item'>
            <?  
                $teams = get_tournament_teams($tid);
                disp_teams_list($teams);
            ?>
            </div>
            <a class='button' id='add-teams'>Add Teams</a>
            <a class='button' id='add-team'>+1</a>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
