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
    disp_header($module['module_name']." : Debug", $js_extra);
    disp_topbar($tourney, $module, 1);
    disp_titlebar($module['module_name']." [DEBUG]");

    $rounds = get_module_rounds($mid);
?>
<div class="con">
    <div class="centerBox"> 
        <div class='mainBox'>
            <?
            echo "<div class='header'>{$tourney['tournament_name']}</div>";
            $t_modules = get_tournament_modules($tid);
            foreach ($t_modules as $m) {
                if ($m['module_id'] == $mid)
                    $class = 'game-finished';
                else
                    $class = '';
                echo "<div class='$class'>{$m['module_name']}</div>";
            }
            ?>
        </div>
        <div class='mainBox'>
            <div class='header'>Teams</div>
            <?
            $teams = get_module_teams($module['module_id']);
            foreach ($teams as $t)
                echo "<div class=''>{$t['team_seed']} : [{$t['team_id']}] {$t['team_name']}</div>";
            ?>
        </div>
        <div class='mainBox'>
            <div class='header'>Rounds</div>
            <? 
            foreach ($rounds as $r)
                echo "<div>{$r['round_number']} : [{$r['round_id']}]</div>\n";
            ?>
        </div>
        <div class='mainBox'>
            <div class='header'>Games</div>
            <?
            function debug_disp_games($games) {
                if ($games) {
                    $status = array( "game-scheduled", "game-finished", "game-inprogress" );
                    foreach ($games as $g) {
                        echo "<div class='{$status[$g['status']]} indent'>[{$g['game_id']}]";
                        $teams = get_game_scores($g['game_id']);
                        if (count($teams) == 1)
                            $teams[0]['score'] = "BYE";
                        foreach ($teams as $t)
                            echo "<span class='indent'>{$t['team_name']}={$t['score']}</span>";
                        echo "</div>";
                    }
                }
                else {
                    echo "<div class='indent game-finished'>EMPTY</div>";
                }
            }
            foreach ($rounds as $r) {
                echo "<div class=''><div class='label'>Round {$r['round_number']}</div>";
                if ($module['module_mode'] == 0) {
                    debug_disp_games( get_round_games($r['round_id']) );
                } else {
                    $r['round_number']--;
                    $st = get_standings($r);
                    $games = get_round_games($r['round_id']);
                    $wb = array_filter($st, function ($t) { return ($t['status'] == 2); });
                    $lb = array_filter($st, function ($t) { return ($t['status'] == 1); });
                    echo "<div class='label'>WB</div>";
                    debug_disp_games( filter_games($r['round_id'], $wb) );
                    if ($module['module_mode'] == 2) {
                        echo "<div class='label'>LB</div>";
                        debug_disp_games( filter_games($r['round_id'], $lb) );
                    }
                    echo "</div>";
                }
            }
            ?>
        </div>
        <div class='mainBox'>
            <div class='header'>Orphans(?)</div>
                Not yet working - right now we compare games against rounds in this module, but this should be over rounds from all modules in tournament
            <?
                foreach ($teams as $t) {
                    $games = sql_select_all("SELECT * FROM tblGame JOIN tblGameTeams USING (game_id) WHERE team_id = ?", array($t['team_id']));
                    foreach ($rounds as $r) {
                        if ($games) {
                            foreach ($games as $idx => $g) {
                                if ($g['round_id'] == $r['round_id'])
                                    unset($games[$idx]);
                            }
                        }
                    }
                    if ($games) {
                        foreach ($games as $g) {
                            echo "<div class=''>{$g['game_id']} - {$g['team_id']}</div>";
                        }
                    }
                }
            ?>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
