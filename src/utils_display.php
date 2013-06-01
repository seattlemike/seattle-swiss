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

function ordinal($cdnl){
    $test_c = abs($cdnl) % 10;
    $ext = ((abs($cdnl) %100 < 21 && abs($cdnl) %100 > 4) ? 'th'
        : (($test_c < 4) ? ($test_c < 3) ? ($test_c < 2) ? ($test_c < 1)
        ? 'th' : 'st' : 'nd' : 'rd' : 'th'));
    return $cdnl.$ext;
}  

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
    $inputs = array( "Name" => "<input $def name='module_title' value=\"{$module['module_title']}\" />",
                     "Notes" => "<input $def name='module_notes' value=\"{$module['module_notes']}\" />",
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
                    "Notes" => "<input $def name='t_notes' value=\"{$tourney['tournament_notes']}\" />",
                    "Who Can See Tournament Results" => $privacy_select);
    // TODO: tournament_notes should be a [text] in the db, and should be a textbox here

    foreach ($inputs as $text => $input) {
        echo "<label>$text $input</label>\n";
    }
}

function disp_modules($modules) {
    $mode = array("Swiss", "Single Elim", "Double Elim");
    if ((! $modules) || (count($modules) == 0))
        echo "<div class='header'>No Brackets Scheduled Yet</div>";
    else {
        echo "<table>\n";
        foreach ($modules as $m) {
            echo "<tr>\n";
            echo "<td class='btnCtr'>\n";
            echo "<a href='/rss/{$m['module_id']}/' title='Follow via RSS'><img src='/img/feed-icon-28x28.png' width='14px' height='14px' /></a>\n";
            echo "</td>\n";
            echo "<td><a href='../../module/{$m['module_id']}/'>{$m['module_title']}</a></td>";
            $date = date("m/d/Y", strtotime($m['module_date']));
            echo "<td>{$mode[$m['module_mode']]}</td><td>$date</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
}

function disp_tournaments($tlist) {
    if ((! $tlist) || (count($tlist) == 0))
        echo "<div class='header'>[no tournaments yet]</div>\n";
    else {
        echo "<table>\n";
        foreach ($tlist as $tourney) {
            echo "<tr>\n";
            $date = date("M d, Y", strtotime($tourney['tournament_date']));
            echo "<td>$date</td>\n";
            // if ($tourney['tournamnent_owner'] == $aid) { $class = 'owner'; }
            // else                                       { $class = ''; }
            echo "<td><a href='tournament/{$tourney['tournament_id']}/'>{$tourney['tournament_name']}</a></td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
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

// displays the navigation for a round
function disp_round_nav($mid, $round, $admin=false) {
    $rounds = get_module_rounds($mid);
    if (! $rounds)
        echo "<div class='header'>Not yet started</div>";
    else {
        $url = $_SERVER['PHP_SELF'];
        foreach ($rounds as $r) {
            if ($r['round_id'] == $round['round_id']) $isSelected = "selected";
            else                        $isSelected = "";
            echo "<a class='button $isSelected' href='$url?round={$r['round_id']}'>Round {$r['round_number']}</a>";
        }
    }

    if ($admin) {
        echo "<form name='round' action='' method='post'>
            <input type='hidden' name='module_id' value='$mid' />
            <input type='hidden' name='round_id' value='{$round['round_id']}' />";
        if (! $rounds)  {
            echo "<input type='hidden' name='case' value='start_module' />
            <input class='button' type='submit' name='start_module' value='Start' />";
        } else {
            // if we're looking at most recent round and round_isdone
            if (($rounds[count($rounds)-1]['round_id'] == $round['round_id']) && round_isdone($round['round_id'])) {
                echo "<input type='hidden' name='case' value='next_round' />
                <input class='button' type='submit' name='next_round' value='Next' />";
            } else {
                echo "<a class='button disabled'>Next</a>";
            }
        }
        echo "</form>";
    }
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
            $views["bracket"] = "Brackets";
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
    // TODO URGENT: grey out lbracket when nrounds==1
    //        pass nrounds to disp_view_nav to do this
    
    $rounds = get_module_rounds($module['module_id']);
    disp_view_nav($module, $rounds, $view);
    switch ($view) {
        case "teams":
            if (! $rounds)
                echo "<div class='header'>Not yet started</div>";
            disp_teams_list($module['module_id']);
            break;
        case "games":
            disp_module_games($module, $rounds);
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
            //disp_sglelim($module, $rounds);
            //disp_dblelim_wbracket($module, $rounds);
            //disp_dblelim_lbracket($module, $rounds);
            break;
    }
}

//
// Display Teams
//
function disp_teams_list($mid) {
    // TODO: starting seed?  sort alphabetically?
    $teams = get_module_teams($mid);
    //$teams = sql_select_all("SELECT * FROM tblTeam WHERE module_id = ? ORDER BY team_name ASC", array($mid));
    if ($teams) {
        echo "<div class='line'><div class='centerCtr'>";
        foreach ($teams as $t) {
            echo "<div class='team'><div class='info'>{$t['team_name']}</div>";
            if ($t['team_text'])
                echo "<div class='dark info'>{$t['team_text']}</div>";
            echo "</div>";
        }
        echo "</div></div>";
    }
}

//
// Display Games
//
function disp_module_games($module, $rounds) {
    switch ($module['module_mode']) {
        case 0:
            foreach (array_reverse($rounds) as $r) {
                echo "<div class='header'>Round {$r['round_number']}</div>";
                disp_games(get_round_games($r['round_id'])); // get rid of byes?
            }
            break;
        case 1:
            $terms = array(8 => "Quarter-finals", 4 => "Semi-finals", 2 => "Finals");
            foreach(array_reverse($rounds) as $r) {
                disp_round_games($r);
            }
            break;
        case 2:
            $wbterms = array(8 => "Quarter-finals", 4 => "Semi-finals", 2 => "Finals");
            $lbterms = array(5=> "Fifth Place Games", 3 => "Losers' Bracket Finals");
            foreach(array_reverse($rounds, true) as $idx => $r) {
                // split round up if both winners and losers
                if (array_key_exists($idx-1, $rounds))
                    $teams = get_standings($rounds[$idx-1]);
                else
                    $teams = get_standings(standings_init_round($module['module_id']));

                $teams = array_filter($teams, function($t) { return ($t['status'] > 0); });

                if (count($teams) == 2) { // FINALS!
                    $lb = array_filter($teams, function ($t) { return ($t['status'] == 1); });
                    if (count($lb) == 1)
                        echo "<div class='header'>Tournament Finals</div>";
                    else 
                        echo "<div class='header'>Final Finals Game</div>";
                    disp_round_games($r); // well, there's only one
                } else { // Not Finals (i.e. regular wb/wb and lb/lb games)
                    $wb = array_filter($teams, function ($t) { return ($t['status'] == 2); });
                    $games = filter_games($r['round_id'], $wb);
                    if ($games) {
                        if ($wbterms[count($wb)])
                            echo "<div class='header'>Winners' Bracket {$wbterms[count($wb)]}</div>";
                        else
                            echo "<div class='header'>Winners' Bracket Round of ".count($wb)."</div>";
                        // filter games for wb
                        disp_games($games);
                    } 
                    $lb = array_filter($teams, function ($t) { return ($t['status'] == 1); });
                    $games = filter_games($r['round_id'], $lb);
                    if ($games) {
                        if ($lbterms[count($teams)])
                            echo "<div class='header'>{$lbterms[count($teams)]}</div>";
                        else
                            echo "<div class='header'>Losers' Bracket ".ordinal(count($teams)-count($games)+1)."-place Games</div>";
                        disp_games($games);
                    }
                }
            }
            break;
    }
}

function disp_games($games, $filter = null) {
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
    if ($games) {
        echo "<div class='games'>\n";
        foreach ($games as $g)
            disp_game($g);
        echo "</div>";
    }
}

function disp_round_games($round) {
    disp_games(get_round_games($round['round_id']));
}

function disp_game($game) {
    $status_class = array("game-scheduled", "game-finished", "game-inprogress");
    $teams = sql_select_all("SELECT * from tblGameTeams a JOIN tblTeam b using (team_id) WHERE a.game_id = ? ORDER BY a.score_id DESC", array($game['game_id']), $db);
    echo "<div class='game {$status_class[$game['status']]}' data-game='".json_encode($game)."' data-score='".json_encode($teams)."'>";

    if (count($teams) == 1)
        echo "{$teams[0]['team_name']} has a BYE";
    else {
        if ($game['status'] == 1) {
            if ($teams[0]['score'] > $teams[1]['score'])
                $teams[0]['team_name'] = "<div class='victor'>{$teams[0]['team_name']}</div>";
            elseif ($teams[0]['score'] < $teams[1]['score'])
                $teams[1]['team_name'] = "<div class='victor'>{$teams[1]['team_name']}</div>";
        }
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
    array_multisort(array_map(function($t) {return $t['rank'];}, $standings), SORT_NUMERIC, $standings);

    // should we display number of draws?  Yes, if anyone has a draw.
    foreach ($standings as $t)
        foreach ($t['results'] as $r)
            if ($r['res'] == 0.5)
                $draws = 1;

    echo "<div class='header'>Standings</div>\n";
    echo "<div class='line'><div class='centerCtr'>";
    foreach ($standings as $seed => $team) {
        echo "<div class='team'>";
        echo "<span class='rank'>".($seed+1)."</span>";
        echo "<span class='' title=\"{$team['text']}\">{$team['name']}\n";
        $result = array('losses', 'draws', 'wins');
        $team['wins'] = $team['losses'] = $team['draws'] = 0;
        foreach ($team['results'] as $r) { // increment appropriate w/l/d column
            $team[$result[$r['res']*2]] += 1;
        }
        echo "<span class='strength'>{$team['wins']}-{$team['losses']}";
        if ($draws)
            echo "-{$team['draws']}";
        echo "</span></span></div>";
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
    // split teams into wb / lb / done (ranked)

    //$teams = array();
    foreach ($standings as $t )
        $teams[$t['status']][] = $t;
    
    foreach($teams as $status => $tlist) {
        echo "$status: ".count($tlist)."<br>";
    }

}

//
//  Here begins the too-much display logic for Single and Double Elimination Brackets
//
//  :(
//

function get_td_color($rnum, $pos) {
    $mod = pow(2, $rnum+2);
    $div = pow(2, $rnum+1);
    $color = intval(($pos % $mod) / $div) ? "light" : "dark";
    return $color;
}

function disp_sglelim($module, $rounds) {
    $st = get_standings($rounds[count($rounds)-1]);
    $nrounds = intval(ceil(log(count($st),2)));
    // rewrite standings results arrays to be indexed by round number
    foreach ($st as $i => $t) {
        $tmp = array();
        foreach ($t['results'] as $r)
            $tmp[$r['rnum']] = $r;
        $st[$i]['results'] = $tmp;
    }

    $bracket = get_bracket($st, range(1, $nrounds) );
    disp_elim( $bracket, pow(2,$nrounds), range(1,$nrounds) );
}

function disp_dblelim_wbracket($module, $rounds) {
    $recent = $rounds[count($rounds)-1];
    $st = get_standings($recent);
    // rewrite standings results arrays to be indexed by round number
    foreach ($st as $i => $t) {
        $tmp = array();
        foreach ($t['results'] as $r)
            $tmp[$r['rnum']] = $r;
        $st[$i]['results'] = $tmp;
    }

    $nrounds = intval(ceil(log(count($st),2)));
    foreach ($st as $idx => $t) {
        $st[$idx]['results'] = array_filter($t['results'], 
            function ($r) use ($nrounds) { return (($r['status'] == 2) || ($r['rnum'] >= 2*$nrounds-1)); });
    }
    
    $rounds = array_merge( array(1,2), range(3,2*$nrounds-3,2) );

    //+1 for final, and possibly double final
    $fin = array(2 * $nrounds);
    if ($rnum >= 2*$nrounds)
        $fin = range(2*$nrounds, $rnum+1);
    $bracket = array_merge( get_bracket($st, $rounds), get_finals($st, $fin) );
    disp_elim($bracket, pow(2,$nrounds), array_merge($rounds, $fin));
}

function disp_dblelim_lbracket($module, $rounds) {
    $recent = $rounds[count($rounds)-1];
    $st = get_standings($recent);

    // rewrite standings results arrays to be indexed by round number
    foreach ($st as $i => $t) {
        $tmp = array();
        foreach ($t['results'] as $r)
            $tmp[$r['rnum']] = $r;
        $st[$i]['results'] = $tmp;
        $st[$i]['color'] = 'light';
    }

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

function get_finals($standings, $rounds) {

    // identify finals teams
    $rnum = $rounds[0];
    $finals = array();
    foreach ($standings as $t) {
        if ($t['results'][$rnum-1]['res']) {  // winner of LB
            $t['upper'] = 1;
            $t['color'] = 'bold';
            $finals[] = $t;
        }
        else if (($t['results'][$rnum-3]['res']) && ($t['results'][$rnum-3]['status'] == 2)) { // winner of WB
            $finals[] = $t;
        }
    }

    // add teams [if any] to bracket
    $bracket = array();
    $center = pow(2,(int) ceil(log(count($standings),2)-1));
    foreach ($rounds as $idx => $rnum) {
        $bracket[$idx] = array();
        foreach ($finals as $t) {
            //debug_alert("late round: $rnum for {$t['name']}");
            if ($idx) {
                if (($t['results'][$rounds[0]]['status'] == 2) && ($t['results'][$rounds[0]]['res'] == 0))
                    $t['color'] = 'bold';
                if (($t['results'][$rnum-1]['res'] == 1) || ($t['results'][$rnum-1]['status'] == 2))
                    $bracket[$idx][$center + $t['upper']] = $t;
            }
            else
                $bracket[$idx][$center + $t['upper']] = $t;
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

    // rnum: 2, 3, 4, ..., 2*nrounds-1
    foreach ($rounds as $i => $rnum) {
        if (! $bracket[$i]) $bracket[$i] = array();
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
                    }
                } else {  // fighting it out among the already-losers
                    $is_upper = ($idx % (2 * $psize)) - ($idx % $psize);
                    //debug_alert("Team {$t['name']}, upper $is_upper");
                    if ($is_upper) $offset = -1 * ($idx % $psize);
                    else           $offset = $psize-1-($idx % $psize);

                    $offset++; // shift all losers-only rounds down one step
                    // if first round & BYE, force upper position
                    if ($is_upper && ($rnum == 2) && ($t['opponents'][1] == -1))
                        $offset--;
                }
                $bracket[$i][$idx+$offset-intval($rnum/2)+1] = $t;
            }
        }
    }
    return $bracket;
}

function get_bracket($standings, $rounds) {
    $bracket = array();
    $nrounds = intval(ceil(log(count($standings),2)));
    $bsize = pow(2, $nrounds);


    // build the display $bracket
    foreach ($rounds as $rnum) {
        $bracket[] = array();
        $psize = pow(2, count($bracket)-1);
        foreach ($standings as $t) {
            if ((! isset($prev)) || ($t['results'][$prev]['res'])) {
                //if ($rnum > 3)
                //    debug_alert("$rnum [$prev], {$t['name']} vs {$t['results'][$prev]['opp_name']}");
                $idx = $t['bracket_idx'];
                $is_upper = ($idx % (2 * $psize)) - ($idx % $psize);
                if ($is_upper) $offset = -1 * ($idx % $psize);
                else           $offset = $psize-1-($idx % $psize);
                $bracket[count($bracket)-1][$idx+$offset] = $t;
            }
        }
        $prev = $rnum;
        $count++;
    }
    return $bracket;
}

function disp_elim($bracket, $height, $rounds) {

    /*echo "<div>COUNT: ".count($bracket[0])."<br>HEIGHT: $height</div>";
    foreach($bracket[0] as $n => $r) {
        echo "<div><div>ROW $n</div>";
        print_r($r);
        echo "</div>";
    }
    */

    // check if we're displaying a losers bracket
    if (2 * count($bracket[0]) <= $height)
        $loser_bracket = true;

    // check if our first round is to weed out a few teams (i.e. at least half byes)
    if (4 * count($bracket[0]) < 3 * $height)
        $bye_round = true;

    //if bye_round, don't show "BYE" games
    if ($bye_round && !$loser_bracket)  {
        foreach( $bracket[0] as $rownum => $r ) {
            if (($rownum % 2 == 0) && (! $bracket[0][$rownum+1]))
                unset($bracket[0][$rownum]);
        }
    }
    // else populate with "BYE" fields
    else { 
        if ($loser_bracket) {
            foreach( $bracket[0] as $rownum => $r ) {
                if ($r['results'][2]['opp_id'] == -1)
                    $bracket[0][$rownum+1] = array("name" => "BYE", "color" => "light");
            }
            foreach( $bracket[1] as $rownum => $r ) {
                if ($r['results'][3]['opp_id'] == -1)
                    $bracket[1][$rownum+1] = array("name" => "BYE", "color" => "light");
            }
        } else {
            foreach( $bracket[0] as $rownum => $r ) {
                if ($r['results'][1]['opp_id'] == -1)
                    $bracket[0][$rownum+1] = array("name" => "BYE", "color" => get_td_color(0, $rownum+1));
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


    // print the table
    echo "<table class='elim standings'>\n";
    echo "<tr><th>Team</th><th>Score</th><th colspan=".(3*count($rounds)-2).">Results</th></tr>\n";
    echo "<tr><th colspan='".(1 + 3 * count($rounds))."'> &nbsp;</th></tr>";
    foreach ($table as $rownum => $row) {
        echo "<tr>";
        foreach (range(0, count($rounds)-1) as $colnum) {
            disp_team($row[$colnum], $colnum, $rounds[$colnum]);
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}

function disp_team($team, $index, $rnum) {
    if ($index > 0) echo "<td class='spacer'></td>\n";  // gap between columns
    if ($team) {
        // figure out color for table entry
        if     (isset($team['color']))   $color = $team['color'];
        elseif ($team['special'])        $color = "bold";
        else                             $color = get_td_color(0, $team['bracket_idx']);

        // team name/seed
        echo "<td class='$color'>";
        echo "<span class='tiny'>{$team['seed']}</span>";
        echo "<span class='result' title=\"{$team['text']}\">{$team['name']}</span> </td>\n";

        // display score
        echo "<td class='numeric $color'>\n";
        if ($team['results'][$rnum] && ($team['results'][$rnum]['opp_id'] != -1))
            echo "<span class='score'>{$team['results'][$rnum]['score'][0]}</span>";
        echo "</td>\n";
    }
    else
        echo "<td colspan=2></td>";  // blank entry in table
}

//
// End crappy bracket display logic
// (which should maybe at this point get its own file)
//

?>
