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


//
//  Disp utilities.  Should sort these a bit.
//

//
// **GENERIC**
//

// button with onclick that modifies form.action
function disp_disabled_button($value, $name="submit_btn") {
    echo "<input class='button' name='$name' value='$value' DISABLED />";
}

function disp_tournament_button($value, $name, $extra='', $class='') {
    $onclick = "this.form.elements[\"case\"].value=\"$name\"; $extra";
    echo "<input onclick='$onclick' class='button $class' type='submit' name='$name' value='$value' />";
}

//
// ** EDIT TOURNAMENT **
//

function disp_status($tourney = null) {
    if (! $tourney)
        $status = "Not yet created";
    elseif ($tourney['is_over'])
        $status = "Finished";
    else {
        list($n, $g, $gtot) = get_tournament_status($tourney['tournament_id']);
        if ($n == 0) {
            $status = "Not yet started";
        }
        else {
            $going=true;
            // check to see if finished
            $status = "Tournament is running!<br>\nRound $n : $g of $gtot games finished.\n";
        }
    }
    ?>
    <div class='mainbox <? if ($going) echo "playing"; ?>'>
        <div class='header'>Status: <? echo $status; ?></div>
    </div>
    <?
}

function build_select($name, $options, $selected = null) {
    $select = "<select name='$name'>";
    foreach ($options as $val => $txt) {
        if ($val == $selected)
            $seltxt = "selected";
        else
            $seltxt = "";
        $select .= "<option value='$val' $seltxt>$txt</option>";
    }
    return $select."</select>";
}

function disp_team_select($mid, $name) {
    echo "<select name='$name'>";
    foreach (get_module_teams($mid) as $t)
        echo "<option value='{$t['team_id']}'>{$t['team_name']}</option>";
    echo "<option value='-1'>BYE</option>";
    echo "</select>";
}

function disp_module_teams($mid, $tournament_teams) {
echo <<<END
    <div id='teams' class="header">Competing this round</div>
    <form name='teams' method='post' action="">
        <input type='hidden' name='action' value='update_seeds' />
        <input type='hidden' name='module_id' value='$mid' />
        <table>
        <tr><th>Team Name</th><th>Status</th><th>Seed</th></tr>
END;
    $module_teams = get_module_teams($mid); // sorted by seed
    $module_team_ids = array();
    foreach ($module_teams as $idx => $team) {
        $team['team_seed'] = $idx+1; // regular 1..n seeds
        disp_module_team($team);
        $module_team_ids[] = $team['module_id'];
    }
    foreach ($tournament_teams as $team) {
        if (! module_hasteam($mid, $team['team_id'])) {
            $team['disabled'] = "DISABLED";
            disp_module_team($team);
        }
    }
    echo "</table> </form> <div class='line'><a class='button disabled' id='save-seeds'>Save Seeds</a></div>\n";
}

function disp_module_details($module = null) {
    if (isset($module))   $date = date("m/d/Y", strtotime($module['module_date']));
    else                   $date = date("m/d/Y");

    //"<input $def name='module_mode' value=\"{$modes[$module['module_mode']]}\" DISABLED />" );
    $modes = array("Swiss Rounds", "Single Elimination", "Double Elimination");
    $mode_select = build_select("module_mode", $modes, $module['module_mode']);
    $def = "class='wide' type='text' maxlength='40'";
    $inputs = array( "Name" => "<input $def name='module_name' value=\"{$module['module_name']}\" />",
                     "Text" => "<input $def name='module_text' value=\"{$module['module_text']}\" />",
                     "Date" => "<input $def name='module_date' value='$date' />",
                     "Mode" => $mode_select );
    // if we've got games in the module, don't allow changes to module_mode
    if (is_array(sql_select_one("SELECT * from tblRound WHERE module_id = ?", array($module['module_id']))))
        $inputs["Mode"] = "<input $def name='mode' value='{$modes[$module['module_mode']]}' DISABLED />";
    // TODO: if module has no associated games yet, don't disable module_mode
    
    foreach ($inputs as $text => $input) {
        echo "<label>$text $input</label>\n";
    }
}

function disp_tournament_details($tourney = null) {
    if (isset($tourney))   $date = date("m/d/Y", strtotime($tourney['tournament_date']));
    else                   $date = date("m/d/Y");

    $privacy_select = build_select("t_privacy", array("Only Me", "Anyone With A Link", "Everyone"), $tourney['tournament_privacy']);
    $def = "class='wide' type='text' maxlength='40'";
    $inputs = array("Name" => "<input $def name='t_name' value=\"{$tourney['tournament_name']}\" />",
                    "Date" => "<input $def name='t_date' value='$date' />", 
                    //TODO: should this be a textbox or an input?
                    "Text" => "<input $def name='t_notes' value=\"{$tourney['tournament_text']}\" />",
                    "Who Can See Tournament Results" => $privacy_select);

    foreach ($inputs as $text => $input) {
        echo "<label>$text $input</label>\n";
    }
}

function disp_modules_list($modules) {
    if ($modules && count($modules)) {
        foreach ($modules as $m) {
            echo "<div class='item'>";
            echo "<a href='".SWISS_ROOT_PATH."rss/{$m['module_id']}/' title='Follow via RSS'><img src='/img/feed-icon-28x28.png' width='14px' height='14px' /></a>";
            echo "<a href='../../module/{$m['module_id']}/'>{$m['module_name']}</a>";
            echo "</div>";
        }
    }
}

function disp_tournament_list($tlist) {
    echo "<div class='list-box'>";
    foreach ($tlist as $tourney) {
        $date = date("M d, Y", strtotime($tourney['tournament_date']));
        echo "<div class='tournament-entry'><span class='date'>$date</span>";
        echo "<a href='tournament/{$tourney['tournament_id']}/'>{$tourney['tournament_name']}</a></div>";
    }
    echo "</div>";
}

function disp_tournaments($tlist) {
    if ((! $tlist) || (count($tlist) == 0))
        echo "<div class='line'>[no tournaments yet]</div>\n";
    else {
        echo "<div class='line'><div class='list-box'>";
        foreach ($tlist as $tourney) {
            $date = date("M d, Y", strtotime($tourney['tournament_date']));
            echo "<div class='tournament-entry'><span class='date'>$date</span>";
            // if ($tourney['tournamnent_owner'] == $aid) { $class = 'owner'; }
            // else                                       { $class = ''; }
            echo "<a href='tournament/{$tourney['tournament_id']}/'>{$tourney['tournament_name']}</a></div>";
        }
        echo "</div></div>";
    }
}

function disp_admins_list($t) {
    $admins = get_tournament_admins($t['tournament_id']);
    foreach ($admins as $a)
        echo "<div class='item'>{$a['admin_name']} ({$a['admin_email']})</div>\n";
}

function disp_admins($t, $aid) {
    echo "<input type='hidden' name='admin_id' value='' />\n";
    foreach (get_tournament_admins($t['tournament_id']) as $admin) {
        // If we're not the owner, then Remove for us only, else remove for all but us
        //   You know, this is a simple xor...
        echo "<p>";
        if ($t['tournament_owner'] == $aid) {
            if ($admin['admin_id'] != $aid)
                disp_tournament_button("Remove", "remove_admin", " this.form.elements[\"admin_id\"].value={$admin['admin_id']};");
        } else {
            if ($admin['admin_id'] == $aid)
                disp_tournament_button("Remove", "remove_admin", " this.form.elements[\"admin_id\"].value={$admin['admin_id']};");
        }
        echo "{$admin['admin_name']} ({$admin['admin_email']})</p>\n";
    }
}

function disp_module_team($team) {
    echo "<tr data-seed='{$team['team_seed']}' data-id='{$team['team_id']}' class='module-team'>";
    echo "<td>{$team['team_name']}</td>";
    if ($team['disabled'])
        echo "<td><a class='button'>Not Competing</a></td>";
    else
        echo "<td><a class='button selected'>Competing</a></td>";
    echo "<td><input name='seed-{$team['team_id']}' class='numeric' type='text' maxlength='6' value='{$team['team_seed']}' {$team['disabled']}/></td></tr>";
}

function disp_team_edit($team) {
    $tid = $team['team_id'];

    echo "<tr><td>\n";
    disp_tournament_button("Update", "update_team", "this.form.elements[\"team_id\"].value=$tid;");
    echo "</td>\n";

    if (!$team['team_uid'])   { $team['team_uid'] = ""; }
    if (!$team['team_init'])  { $team['team_init'] = ""; }
    echo "<td><input type='text' name='name_$tid' value=\"{$team['team_name']}\"></td>\n";
    echo "<td><input type='text' name='text_$tid' value=\"{$team['team_text']}\"></td>\n";
    echo "<td><input class='numeric' type='text' name='uid_$tid' value=\"{$team['team_uid']}\"></td>\n";
    echo "<td><input class='numeric' type='text' name='init_$tid' value='{$team['team_init']}'></td>\n";

    echo "<td>";
    disp_tournament_button("Disable", "disable_team", "this.form.elements[\"team_id\"].value=$tid;",
        $team['is_disabled'] ? "selected" : "");
    echo "</td>\n";
    echo "<td>";
    if (team_can_delete($team['team_id']))
        disp_tournament_button("Delete", "delete_team", "this.form.elements[\"team_id\"].value=$tid;");
    else
        echo "<input class='button disabled' type='submit' name='delete_team' value='Delete' DISABLED />";
    echo "</td>\n";

    echo "</tr>\n";
}

function disp_view_nav($module, $rounds, $view) {
    echo "<div class='nav'>";
    $views = array("teams" => "Teams","games" => "Games");
    switch ($module['module_mode']) {
        case 0:
            $views["standings"] = "Standings";
            break;
        case 1:
            $views["bracket"] = "Bracket";
            $views["standings"] = "Standings";
            break;
        case 2:
            $views["wbracket"] = "Winners' Bracket";
            $views["lbracket"] = "Losers' Bracket";
            $views["standings"] = "Standings";
            break;
    }

    $selected = array($view => "selected");
    foreach ($views as $v => $text)
        echo "<a class='button {$selected[$v]}' href='$v'>$text</a>";
    echo "</div>";
}

// ASSERT: check is_public/has_privs has already happened
function disp_standings($module, $view=null) {
    $rounds = get_module_rounds($module['module_id']);
    disp_view_nav($module, $rounds, $view);
    switch ($view) {
        case "teams":
            echo "<div class='line'>";
            if (! $rounds)
                echo "<div class='header'>Not yet started</div>";
            disp_teams_list(get_module_teams($module['module_id']));
            echo "</div>";
            break;
        case "games":
            echo "<div class='line' id='module'>";
            disp_module_games($module, $rounds);
            echo "</div>";
            break;
        case "debug":
            //disp_swiss($module['module_id'], count($rounds), true);
            break;
        case "standings":
            if (count($rounds > 0)) {
                $standings = get_standings($rounds[count($rounds)-1]);
                if (count($standings) == 0) { throw new Exception("Problem: attempted to view empty standings"); }
                switch($module['module_mode']) {
                    case 0: // Swiss
                        disp_standings_swiss($module, $standings);
                        break;
                    case 1: // Single Elim
                    case 2: // Double Elim
                        disp_standings_elim($module, $standings);
                        break;
                }
            }
            break;
        case "bracket":
            if ($module['module_mode'] == 1) {
                echo "<div class='header'>Elimintaion Bracket</div>";
                disp_sglelim($module, $rounds);
            }
            break;
        case "wbracket":
            if ($module['module_mode'] == 2) {
                echo "<div class='header'>Winners' Bracket</div>";
                disp_dblelim_wbracket($module, $rounds);
            }
            break;
        case "lbracket":
            if ($module['module_mode'] == 2) {
                echo "<div class='header'>Losers' Bracket</div>";
                disp_dblelim_lbracket($module, $rounds);
            }
            break;
    }
}

//
// Display Teams
//
function disp_teams_list($teams) {
    // TODO: starting seed?  sort alphabetically?
    echo "<div class='list-box' id='teams-list'>";
    if ($teams && count($teams)) {
        foreach ($teams as $t) {
            echo "<div class='team' data-name='{$t['team_name']}' data-text='{$t['team_text']}' data-uid='${t['team_uid']}' data-teamid='${t['team_id']}'>";
            echo "<div class='team-name info'>{$t['team_name']}</div>";
            echo "<div class='team-text'>{$t['team_text']}</div></div>";
        }
    }
    echo "</div>";
}


//
// Display Games
//
function disp_module_games($module, $rounds) {
    foreach (array_reverse($rounds) as $round)
        foreach (get_round_data($round['round_id'], $module) as $r)
            disp_round_games($r['rid'], $r['title'], $r['games']);
}

function disp_games($games, $filter = null) {
    echo "<div class='games-list'>\n";
    if ($filter) {
        foreach($filter as $idx => $t) {
            echo "<br>Team: $idx<br>";
            print_r($t);
        }
        foreach ($games as $idx => $g) {
            echo "<br>Game: $idx<br>";
            print_r($g);
        }
        die();

       //$games = array_filter($games, function ($t) use ($filter) { return 
    }
    if ($games)
        foreach ($games as $g)
            disp_game($g);
    echo "</div>";
}

function disp_round_games($rid, $title, $games) {
    echo "<div class='round' data-rid='$rid'>";
    echo "<div class='header'>$title</div>";
    disp_games($games); // get rid of byes?
    echo "</div>";
}

function disp_game($game) {
    $status_class = array("game-scheduled", "game-finished", "game-inprogress");
    $teams = sql_select_all("SELECT * from tblGameTeams a JOIN tblTeam b using (team_id) WHERE a.game_id = ? ORDER BY a.score_id DESC", array($game['game_id']), $db);
    echo "<div class='game {$status_class[$game['status']]}' data-game='".json_encode($game, JSON_HEX_APOS)."' data-score='".json_encode($teams, JSON_HEX_APOS)."'>";

    if (count($teams) == 1)
        echo "{$teams[0]['team_name']} has a BYE";
    else {
        if ($game['status'] == 1) {
            if ($teams[0]['score'] > $teams[1]['score'])
                $teams[0]['team_name'] = "<span class='victor'>{$teams[0]['team_name']}</span>";
            elseif ($teams[0]['score'] < $teams[1]['score'])
                $teams[1]['team_name'] = "<span class='victor'>{$teams[1]['team_name']}</span>";
        }
        //foreach ($teams as $i => $t)
        //    $teams[$i]['team_name'] = "<span class='{$t['class']}'>{$t['team_name']}</span>";
        
        echo "{$teams[0]['team_name']} vs {$teams[1]['team_name']}";

        if ($game['status'] > 0) {
            echo ", {$teams[0]['score']}-{$teams[1]['score']}";
        }
    }
    echo "</div>";
}

//
// Standings Display
//

function disp_places($round) {
    $standings = get_standings($round);
    array_multisort(array_map(function($t) {return $t['rank'];}, $standings), SORT_NUMERIC, 
                    array_map(function($t) {return $t['name'];}, $standings), SORT_STRING,
                    $standings);
    foreach ($standings as $t) {
        echo "<div><div class='seed'><span class='rank'>{$t['rank']}</span><span class='team'>{$t['name']}</span></div></div>";
    }
}

function disp_standings_swiss($module, $standings) {
    // TODO IMMEDIATE: figure out how we're going to deal with partially completed rounds (split standings)
    // TODO: option to sort by record (buchholz tie-break) or by strength (iterative calc)
    array_multisort(array_map(function($t) {return $t['maxprob'];}, $standings), SORT_NUMERIC, SORT_DESC, $standings);

    // should we display number of draws?  Yes, if anyone has a draw.
    foreach ($standings as $t)
        foreach ($t['results'] as $r)
            if ($r['res'] == 0.5)
                $draws = 1;

    echo "<div class='header'>Standings</div>\n";
    echo "<div class='line'><div class='list-box'>";
    foreach ($standings as $seed => $team) {
        $result = array("losses", "draws", "wins");
        $team['wins'] = $team['losses'] = $team['draws'] = 0;
        foreach ($team['results'] as $r) { // increment appropriate w/l/d column
            $team[$result[$r['res']*2]] += 1;
        }
        $record = $draws ? "{$team['wins']}-{$team['losses']}-{$team['draws']}" : "{$team['wins']}-{$team['losses']}";

        echo "<div class='rank-team viable' data-team='".json_encode($team, JSON_HEX_APOS)."'>";
        echo "<div class='rank'>".($seed+1)."</div>";
        echo "<div class='team-name'>{$team['name']}</div>";
        echo "<div class='strength'>".sprintf("%.3f",$team['maxprob'])."</div>";
        echo "<div class='record'>$record</div>";
        //if ($draws)
        //    echo "-{$team['draws']}";
        //echo "</div>";
        echo "</div>\n";
        //echo "<span class='strength'>".sprintf("%.4f",$team['maxprob'])."</span></div>";
    }
    echo "</div></div>";
    //echo "<td class='bold numeric'>{$team['score']}</td>";
    //echo "<td class='bold numeric'>".sprintf("%.4f",$team['maxprob'])."</td>";
    //echo "<td class='numeric'>{$team['buchholz']}</td>";
    //echo "<td class='numeric'>".sprintf("%.2f",$team['srs'])."</td>";
    //echo "<td class='numeric'>".sprintf("%.4f",$team['iterBuchholz'])."</td>";
}

function disp_standings_elim($module, $standings) {
    //$teams = array();
    array_multisort(array_map(function($t) {return $t['rank'];}, $standings), SORT_NUMERIC, 
                    array_map(function($t) {return $t['name'];}, $standings), SORT_STRING,
                    $standings);

    foreach ($standings as $t )
        $teams[$t['status']][] = $t;

    foreach ($teams as $s => $grp) {
        if ($s == 0) {
            array_multisort(array_map(function($t) {return $t['rank'];}, $grp), SORT_NUMERIC, 
                            array_map(function($t) {return $t['name'];}, $grp), SORT_STRING,
                            $teams[$s]);
        } else {
            array_multisort(array_map(function($t) {return $t['seed'];}, $grp), SORT_NUMERIC, 
                            $teams[$s]);
        }
    }
    
    if (count($teams[1]) + count($teams[2]) == 1) {
        // Tournament is over
        echo "<div class='header'>Final Standings</div>";
        echo "<div class='games-list'>";
        foreach ($standings as $t)
            echo "<div class='rank-team'><div class='rank'>{$t['rank']}</div><div class='team-name'>{$t['name']}</div></div>";
        echo "</div>";
    } else {
        switch ($module['module_mode']) {
            case MODULE_MODE_SGLELIM:
                $cats = array(2 => "Still In", 0 => "Finished");
                break;
            case MODULE_MODE_DBLELIM:
                $cats = array(2 => "Winners' Bracket", 1 => "Losers' Bracket", 0 => "Finished");
                break;
        }
        foreach ($cats as $s => $title) {
            if (count($teams[$s])) {
                echo "<div class='header'>$title</div>";
                echo "<div class='games-list'>";
                if ($s == 0) {  // teams with rank
                    foreach ($teams[$s] as $t)
                        echo "<div class='rank-team'><div class='rank'>{$t['rank']}</div><div class='team-name'>{$t['name']}</div></div>";
                } else {  // teams, no rank displayed
                    foreach ($teams[$s] as $t)
                        echo "<div class='team'><div class='seed'>{$t['seed']}</div><div class='team-name'>{$t['name']}</div></div>";
                }
                echo "</div>";
            }
        }
    }

}

//
//  Here begins the too-much display logic for Single and Double Elimination Brackets
//
//  :(
//

//re-index results array by round number
function reorder_results($results) {
    $tmp = array();
    foreach ($results as $r)
        $tmp[$r['rnum']] = $r;
    return $tmp;
}

function get_td_color($rnum, $pos) {
    $mod = pow(2, $rnum+2);
    $div = pow(2, $rnum+1);
    $color = intval(($pos % $mod) / $div) ? "light" : "dark";
    return $color;
}

function disp_sglelim($module, $rounds) {
    $st = get_standings($rounds[count($rounds)-1]);
    foreach (array_keys($st) as $i)  // re-index team['results'] by round-number
        $st[$i]['results'] = reorder_results($st[$i]['results']);
    $nrounds = intval(ceil(log(count($st),2)));
    $bracket = get_bracket($st, range(1, $nrounds) );
    disp_elim( $bracket, pow(2,$nrounds), range(1,$nrounds) );
}

function disp_dblelim_wbracket($module, $rounds) {
    $st = get_standings($rounds[count($rounds)-1]);
    foreach (array_keys($st) as $i)  // re-index team['results'] by round-number
        $st[$i]['results'] = reorder_results($st[$i]['results']);

    $nrounds = intval(ceil(log(count($st),2)));
    foreach ($st as $idx => $t) {
        $st[$idx]['results'] = array_filter($t['results'], 
            function ($r) use ($nrounds) { return (($r['status'] == 2) || ($r['rnum'] >= 2*$nrounds-1)); });
    }
    
    $rounds = array_merge( array(1,2), range(3,2*$nrounds-3,2) );
    //+1 for final, and possibly double final
    //$fin = array(2 * $nrounds);
    //if ($rnum >= 2*$nrounds)
    $finals = range(2*$nrounds, 2 * $nrounds+1);
    $bracket = array_merge( get_bracket($st, $rounds), get_dblelim_finals($st, 2*$nrounds) );
    disp_elim($bracket, pow(2,$nrounds), array_merge($rounds, $finals));
    //disp_bracket($bracket);
}

function disp_dblelim_lbracket($module, $rounds) {
    $st = get_standings($rounds[count($rounds)-1]);
    foreach (array_keys($st) as $i)  // re-index team['results'] by round-number
        $st[$i]['results'] = reorder_results($st[$i]['results']);
    //$st[$i]['color'] = 'light';

    // number of winners bracket rounds with losers?
    $nrounds = intval(ceil(log(count($st),2)));
    foreach ($st as $idx => $t) {
        // add blank next-games where needed
        //w1->l2, w2->l3, w3->l5, w5->l7
        foreach ($t['results'] as $rnum => $r) {
            if (($r['status'] == 2) && ($rnum > 2))
                $next = $rnum + 2;
            else
                $next = $rnum + 1;


            if ((($r['status'] == 2) && ($r['res'] == 0)) || 
                (($r['status'] == 1) && ($r['res'] == 1)))
                $t['results'][$next]['status'] = 1;  // creates blank if needed.
        }

        // filter to only return losers bracket games
        $st[$idx]['results'] = array_filter($t['results'], 
            function ($r) use ($nrounds) { 
                return (($r['status'] == 1) && ($r['rnum'] < 2*$nrounds)); });
    }
    
    $rounds = range(2,2*$nrounds-1);
    disp_elim(get_lbracket($st, $rounds), pow(2,$nrounds), $rounds, 'loser_idx');
}

function get_dblelim_finals($standings, $finals) {
    $center = pow(2,(int) ceil(log(count($standings),2)-1))-1;

    // 'blank' bracket
    $bracket = array( array( $center-1 => array("name"=>"Tournament Finals", "color" => "grey", "upper" => 1),
                             $center   => array("name"=>"[Winner of LB]", "color" => "bold", "upper" => 0)),
                      array( $center-2 => array("name"=>"Second Finals", "color" => "grey", "upper" => 1),
                             $center-1 => array("name"=>"(if necessary)", "color" => "grey", "upper" => 0)));
    // identify teams in finals, if we have them yet
    foreach ($standings as $t) {
        if ($t['results'][$finals-1]['res']) {  // winner of LB
            $t['color'] = 'bold';
            $t['upper'] = 0;
            $teams['lb'] = $t;
        }
        else if (($t['results'][$finals-3]['res']) && ($t['results'][$finals-3]['status'] == 2)) { // winner of WB
            $t['upper'] = 1;
            $teams['wb'] = $t;
        }
    }
    if ($teams) {     // if we have teams in the finals
        // first finals (when teams are known)
        if ($teams['wb'])
            $bracket[0][$center-1] = $teams['wb'];
        if ($teams['lb'])
            $bracket[0][$center] = $teams['lb'];

        if ($teams['wb']['results'][$finals]['res']) { // one-game finals
            $bracket[0] = array($center-1 => $teams['wb'], $center => $teams['lb']);
            // winner!
            $bracket[1] = array($center-2 => array("name" => $teams['wb']['name'], "color" => "winner", "upper" => 2));
            //$bracket[1] = array($center-2 => array("name" => $team['wb']['name'], "color" => "winner", "upper" => 2));
        }
        elseif ($teams['lb']['results'][$finals]) { // second finals
            $teams['wb']['color'] = 'bold';
            $bracket[1] = array($center-2 => $teams['wb'], $center-1 => $teams['lb']);
            // winner!
            if ($teams['lb']['results'][$finals+1])
                $winner = $teams['lb']['name'];
            else if ($teams['wb']['results'][$finals+1])
                $winner = $teams['wb']['name'];
            if ($winner)
                $bracket[2] = array($center-3 => array("name" => $winner, "color" => "winner", "upper" => 2));
        }


    }
    return $bracket;
}

// takes $standings, $rounds array, and outputs losers bracket over those rounds
function get_lbracket($standings, $rounds) {
    $bracket = array();
    $nrounds = intval(ceil(log(count($standings),2)));
    $bsize = pow(2, $nrounds);

    $bracket[] = array();

    $nteams_wb = count($standings);
    $team_idx = array(0);
    $skip = $bsize / 2;
    while (count($team_idx) < $nteams_wb) {
        for ($i=count($team_idx); $i > 0 && count($team_idx) < $nteams_wb; $i--)
            $team_idx[] = $team_idx[$i-1]+$skip;
        $skip /= 2;
    }


    // TODO: refactor the actual lbracket display to work inside of the grey-display?
    $del = $nteams_wb - pow(2, intval(ceil(log($nteams_wb,2))-1));
    $nteams_wb -= $del;
    $nteams_lb += $del;
    foreach ($rounds as $i => $rnum) {
        $psize = pow(2, intval($rnum / 2)); // pocket size for single space in bracket

        //echo "<div>$nteams_wb / $nteams_lb</div>";

        if ($nteams_wb < $nteams_lb) {
            $del = $nteams_lb - $nteams_wb;
            $nsolo = $nteams_lb - 2*$del;
            //echo "<div>Psize: $psize, Del: $del, Nsolo: $nsolo</div>";
            for ($x=$nteams_wb; $x < $nteams_lb+$nteams_wb; $x++) {
                $idx = $team_idx[$x];
                $is_upper = ($idx % $psize == 0);
                if ($is_upper)  $offset = -1 * ($idx % $psize);
                else            $offset = 1-($idx % $psize);
                $offset--;
                //$offset++; // shift all losers-only rounds down one step
                if ($x < $nteams_wb + $nsolo)
                    $is_upper = 2;
                $bracket[$i][$idx+$offset-intval($rnum/2)+1] = array('color' => 'grey', 'upper' => $is_upper);
            } 

        } else {
            $del= max(0, $nteams_lb - $nteams_wb / 2);// ngames in lb
            $nsolo = $nteams_lb - 2*$del;
            //echo "<div>#Psize: $psize, Del: $del, Nsolo: $nsolo</div>";
            for ($x=$nteams_wb; $x < $nteams_lb+$nteams_wb; $x++) {
                $idx = $team_idx[$x];
                // dropping down: 
                $is_upper = ($idx % (2 * $psize) != $idx % $psize);
                //if ($old_upper) $offset = -1 * ($idx % $psize);
                //else            $offset = $psize-1-($idx % $psize)+1;
                if ($is_upper)  $offset = -1 * ($idx % $psize);
                else            $offset = 1 - ($idx % $psize)+$psize;

                // solo?
                if ($x < $nteams_wb + $nsolo)
                    $is_upper = 2;
                $bracket[$i][$idx+$offset-intval($rnum/2)+1] = array('color' => 'grey', 'upper' => $is_upper);
            }
            $nteams_wb /= 2;
            $del -= $nteams_wb;  // incoming teams from wb
        }
        $nteams_lb -= $del;
        

        //if (! $bracket[$i]) $bracket[$i] = array();
        $psize = pow(2, intval($rnum / 2)); // pocket size for single space in bracket
        foreach ($standings as $t) {
            if ($t['results'][$rnum]) {
                $idx = (int) $t['loser_idx'] / 2;
                if ($rnum % 2) {  // has teams new to losers bracket
                    $old_upper = ($idx % (2 * $psize)) - ($idx % $psize);
                    if ($old_upper) $offset = -1 * ($idx % $psize);
                    else            $offset = $psize-1-($idx % $psize)+1;

                    $is_upper = (! $t['results'][$rnum-1]['res']);
                    if ($is_upper) { 
                        $offset--;
                        $t['color'] = 'dark';
                    } else {
                        $t['color'] = 'light';
                    }
                } else {  // fighting it out among the already-losers
                    $t['color'] = 'light';
                    $is_upper = ((($idx % (2 * $psize)) - ($idx % $psize)) == 0);
                    //debug_alert("Team {$t['name']}, upper $is_upper");
                    if (! $is_upper) $offset = -1 * ($idx % $psize);
                    else             $offset = $psize-1-($idx % $psize);

                    $offset++; // shift all losers-only rounds down one step
                    // if first round & BYE, force upper position
                    if (! $is_upper && ($rnum == 2) && ($t['opponents'][1] == -1))
                        $offset--;
                }
                $t['upper'] = $is_upper;
                $bracket[$i][$idx+$offset-intval($rnum/2)+1] = $t;
            }
        }
    }

    return $bracket;
}

// NOTE:  doesn't include double elim finals / final-finals
function get_bracket($standings, $rounds) {
    $bracket = array();
    $nrounds = intval(ceil(log(count($standings),2)));
    $bsize = pow(2, $nrounds);

    foreach ($standings as $idx => $t)
        $standings[$idx]['color'] = ($t['bracket_idx'] % 4 < 2 ) ? 'dark' : 'light';
    // build the display $bracket
    foreach ($rounds as $rnum) {
        $bracket[] = array();
        $psize = pow(2, count($bracket)-1);
        foreach ($standings as $t) {
            $idx = $t['bracket_idx'];
            $upval = ($idx % (2 * $psize)) - ($idx % $psize);
            $t['upper'] = ($upval == 0);
            if ($t['upper'])
                $offset = $psize-1-($idx % $psize);
            else
                $offset = -1 * ($idx % $psize);
            if ((! isset($prev)) || ($t['results'][$prev]['res'])) {
                // display hints added to $t:
                //     top/bottom.  placeholder.  insertion.
                //$t['post-line'] = 
                $bracket[count($bracket)-1][$idx+$offset] = $t;
                //if ($rnum > 3)
                    //debug_alert("$rnum [$prev], {$t['name']} vs {$t['results'][$prev]['opp_name']}, idx=$idx, is_upper=$upval");
            } else {
                if (!isset($bracket[count($bracket)-1][$idx+$offset]))
                    $bracket[count($bracket)-1][$idx+$offset] = array('color' => 'grey', 'upper' => ! $upval);
            }
        }
        $prev = $rnum;
        $count++;
    }
    return $bracket;
}

function disp_elim($bracket, $height, $rounds) {
    $in_loser_bracket = (2 * count($bracket[0]) <= $height );

    // properly display byes
    if ($in_loser_bracket) {
        foreach( $bracket[0] as $rownum => $r ) {
            if ($r['results'][2]['opp_id'] == -1) {
                $bracket[0][$rownum]['upper'] = 2;
            }
        }
        foreach( $bracket[1] as $rownum => $r ) {
            if ($r['results'][3]['opp_id'] == -1) {
                $bracket[1][$rownum]['upper'] = 2;
            }
        }
    } else {
        foreach( $bracket[0] as $rownum => $r ) {
            if ($r['results'][1]['opp_id'] == -1) {
                $bracket[0][$rownum]['upper'] = 2;
            }
        }
    }
   
    // $bracket is ordered by columns (rounds), $table will be ordered by table-row
    // initialize table with $height-many blank rows
    $table = array_map(function ($r) {return array();}, range(1, $height));
    // loop through bracket columns and add to appropriate table row
    foreach ($bracket as $colnum => $results) {
        foreach ($results as $rownum => $r) {
            $table[$rownum][$colnum] = $r;
        }
    }

    echo "<table class='elim standings'>\n";
    foreach ($table as $rownum => $row) {
        echo "<tr>";
        if ($in_loser_bracket) {
            $pow = 4;
            $offset = 0;
        } else {
            $pow = 2;
            $offset = 1;
        }
        //foreach (range(0, count($rounds)-1) as $colnum) {
        foreach ($bracket as $colnum => $column) {
            $team = $row[$colnum];
            disp_team($team, $colnum, $rounds[$colnum]);

            // Draw connecting lines

            // if not yet in last round(s):  $colnum < count($rounds)-1) {
            if ($pow < count($table)) {
                if (($in_loser_bracket) && ($colnum % 2 == 0)) {
                    $offset += 1;
                    $pos = ($rownum + $offset) % $pow;
                    if ($pos == $pow/2 +1)  // hook up
                        echo "<td class='spacer bottom-left'></td>\n";
                    else // space
                        echo "<td class='spacer'></td>";

                    if ($pos == $pow/2)  // dash
                        echo "<td class='spacer top-right'></td>";
                    else  // space
                        echo "<td class='spacer'></td>";
                } else {
                    $pow *= 2;
                    $pos = ($rownum + $offset-1) % $pow;

                    if ($pos == $pow/4) // hook down
                        echo "<td class='spacer top-left'></td>\n";
                    elseif ($pos == 3*$pow/4-1) // hook up
                        echo "<td class='spacer bottom-left'></td>\n";
                    elseif ($pos > $pow/4 && $pos < 3*$pow/4-1) // vertical bar
                        echo "<td class='spacer spacer-right'></td>";
                    else // space
                        echo "<td class='spacer'></td>";
                        
                    if ($pos == $pow/2-1) { // dash
                        echo "<td class='spacer top-right'></td>";
                    }
                    else  // space
                        echo "<td class='spacer'></td>";
                }
            } else { // last two rows
                if ($colnum < count($bracket)-1) {
                    $offset += 1;
                    $pos = ($rownum + $offset) % $pow;
                    if ($pos == $pow/2 +1)  // hook up
                        echo "<td class='spacer bottom-left'></td>\n";
                    else // space
                        echo "<td class='spacer'></td>";

                    if ($pos == $pow/2)  // dash
                        echo "<td class='spacer top-right'></td>";
                    else  // space
                        echo "<td class='spacer'></td>";
                } else {
                    echo "<td class='spacer'></td>\n";
                    echo "<td class='spacer'></td>\n";
                }
            }
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}

function disp_team($team, $index, $rnum) {
    if ($team) {
        if ($team['upper'] == 0)
            $team['class'] = 'standings-bottom';
        elseif ($team['upper'] == 1)
            $team['class'] = 'standings-top';
        else
            $team['class'] = 'standings-solo';

        // figure out color for table entry
        if     (isset($team['color']))   $color = $team['color'];
        elseif ($team['special'])        $color = "bold";
        else                             $color = get_td_color(0, $team['bracket_idx']);

        // team name/seed
        echo "<td class='standings-entry'>";
        echo "<div class='$color standings-team {$team['class']}'>";
        //echo "<span class='tiny'>{$team['seed']}</span>";
        echo "<span class='team-name' title=\"{$team['text']}\">{$team['name']}</span>\n";

        // display score
        echo "<div class='score'>\n";
        if ($team['results'][$rnum] && ($team['results'][$rnum]['opp_id'] != -1))
            echo "<span class='score'>{$team['results'][$rnum]['score'][0]}</span>";
        echo "</div></div></td>\n";
    }
    else
        echo "<td></td>";  // blank entry in table
}

//
// End crappy bracket display logic
// (which should maybe at this point get its own file)
//

?>
