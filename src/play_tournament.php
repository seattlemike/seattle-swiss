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

    // Ensure mid/tid are valid and that we have privs to edit
    ($mid = $_POST['module_id']) || ($mid = $_GET['module']);
    $module = get_module($mid) ;
    if (! $module) 
        header_redirect("/private/");
    $tid = $module['parent_id'];
	require_login();
    require_privs( tournament_isadmin($tid, $_SESSION['admin_id']) );
    $tourney = get_tournament($tid);
    if (! $round) {
        ($round = get_current_round($mid)) 
        && ($rid = $round['round_id']);
    }

    $js_extra = array("/ui.js", "/async.js", "/run_module.js");
    $header_extra = array( '<script type="text/javascript">window.onload = runModuleOnLoad</script>' );
    disp_header($module['module_name']." : Run", $js_extra, $header_extra);
    disp_topbar($tourney, $module, 2);
    disp_titlebar($module['module_name']);

    $rounds = get_module_rounds($mid);

// Display message about no-teams-yet with relocate to appropriate place to add?
//      if (count(get_tournament_teams($tid)) && count(get_module_teams($mid))) {
?>
<div class="con">
    <div class="centerBox"> 
        <div id='run-module' class='mainBox'>
            <?
            echo "<div class='nav'><a id='next' class='button disabled'>Next Round</a></div>";
            $data_mteams = json_encode(get_module_teams($module['module_id']), JSON_HEX_APOS);
            $data_module = json_encode($module, JSON_HEX_APOS);
            echo "<div id='module' data-module='$data_module' data-teams='$data_mteams'>";
            disp_module_games($module, $rounds);
            echo "</div>";
            ?>
        </div>
    </div>
</div>
<div class='light-box' id='edit-games'>
    <div class='header' id='edit-header'>Edit Games</div> 
        <div class='line' id="edit-controls">
            <a class='button' id='game-add'>Add Game</a>
            <a class='button' id='game-del'>Delete Game</a>
            <a class='button' id='game-move'>Move Game</a>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
