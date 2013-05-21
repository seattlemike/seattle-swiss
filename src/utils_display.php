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

function disp_admin_tournament($t, $aid, $idx) {
    if ($t['tournament_owner'] == $aid)
        $class = "owner";
    // if tournament is running { $class="running" }
    if (($idx % 2) == 0)
        $class.="even";
    else
        $class.="odd";
    echo "<tr class='$class'>";
    echo "<td>";
    echo "<a class='button' href='tournament.php?id={$t['tournament_id']}'>Edit</a>";
    echo "<a class='button' href='play_tournament.php?id={$t['tournament_id']}'>Run</a>";
    echo "</td>";
    echo "<td>" . date("M d, Y", strtotime($t['tournament_date'])) . "</td>";
    echo "<td>{$t['tournament_name']}</td>";

    echo "</tr>";
}

function disp_modules($tlist) {
    $mode = array("Swiss", "Single Elim", "Double Elim");
    if ((! $tlist) || (count($tlist) == 0))
        echo "<div class='line'>[no brackets scheduled yet]</div>\n";
    else {
        echo "<table>\n";
        foreach ($tlist as $m) {
            echo "<tr>\n";
            echo "<td class='btnCtr'>\n";
            echo "<a href='/rss/{$m['module_id']}/' title='Follow via RSS'><img src='/img/feed-icon-28x28.png' width='14px' height='14px' /></a>\n";
            echo "</td>\n";
            echo "<td><a href='module.php?module={$m['module_id']}'>{$m['module_title']}</a></td>";
            $date = date("m/d/Y", strtotime($m['module_date']));
            echo "<td>{$mode[$m['module_mode']]}</td><td>$date</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
}

function disp_tournaments($tlist, $dest='tournament.php') {
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
            echo "<td><a href='$dest?id={$tourney['tournament_id']}'>{$tourney['tournament_name']}</a></td>";
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
        echo "<td><a class='hot button'>Competing</a></td>";
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
    if (! $rounds) {
        if ($admin)
            echo "<input class='button' type='submit' name='start_module' value='Start' />";
        else
            echo "<div class='header'>Not yet started</div>";
    } else {
        $url = $_SERVER['PHP_SELF'];
        foreach ($rounds as $r) {
            if ($r['round_id'] == $round['round_id']) $isSelected = "selected";
            else                        $isSelected = "";
            echo "<a class='button $isSelected' href='$url?round={$r['round_id']}'>Round {$r['round_number']}</a>";
        }
        if ($admin) {
            if (isset($rid)) {
                $next_round = tournament_next_round($rid);
                if (tournament_round_is_done($rid) && ! $next_round) {
                    $onclick = " this.form.elements[\"populate_id\"].value=\"{$next_round['round_id']}\";";
                    disp_tournament_button("Next", "populate_round", $onclick);
                }
                else
                    echo "<a class='button disabled'>Next</a>";
            }
        }
    }
}

function disp_teams_list($mid) {
    //TODO: grey out teams that have been disabled
    //TODO: if team_init is nonzero, display this somewhere
    echo "<div class='mainbox'>\n";
    $teams = sql_select_all("SELECT * FROM tblTeam WHERE tournament_id = :tid ORDER BY team_name ASC", array(":tid" => $tid));
    if ($teams) {
        foreach ($teams as $t) {
            if ($t['team_text']) $text = " : <i>{$t['team_text']}</i>";
            else                 $text = "";
            echo "<p>{$t['team_name']}$text</p>\n";
        }
    }
    echo "</div></div>\n";
}

//
// **STANDINGS**
//

function disp_view_nav($tid, $is_started, $url, $view=null) {
    echo "<div class='nav'>";
    $mode = get_tournament_mode($tid);
    $views = array("teams" => "Teams","games" => "Games");
    switch ($mode) {
        case 0:
            $views["results"] = "Results";
            break;
        case 1:
            $views["bracket"] = "Bracket";
            break;
        case 2:
            $views["wbracket"] = "Winners Bracket";
            $views["lbracket"] = "Losers Bracket";
            break;
    }
    $views["standings"] = "Standings";

    foreach ($views as $v => $text) {
        if ($is_started || $v == "teams") {
            if ($view == $v) $class = "selected";
            else             $class = "";
            echo "<a class=\"button $class\" href=\"$url{$v}\">$text</a>";
        }
        else
            echo "<a class=\"button disabled\">$text</a>";

    }
    echo "</div>";
}

// ASSERT: check is_public/has_privs has already happened
function disp_standings($module, $view=null) {
    $rounds = get_module_rounds($module['module_id']);

    // default view if nothing explicitly chosen
    if (! $view) {
        if (! $rounds)
            $view = "teams";
        else {
            if (isset($_GET['round_id']))
                $view = "games";
            else
                switch ($mode) {
                    case 0:
                        $view = "results";
                        break;
                    case 1:
                        $view = "bracket";
                        break;
                    case 2:
                        $view = "wbracket";
                        break;
                }
        }
    }

    // view_nav_buttons [mostly greyed out when nrounds=0]
    // TODO URGENT: grey out lbracket when nrounds==1
    //        pass nrounds to disp_view_nav to do this
    
    //disp_view_nav($mid, ($nrounds > 0), "view.php?id=$tid&view=", $view);

    switch ($view) {
        case "teams":
            echo "<div class='mainBox'>";
            if (! $rounds)
                echo "<div class='header'>Not yet started</div>";
            disp_teams_list($mid);
            echo "</div>";
            break;
        case "games":
            echo "<div class='mainBox'>\n";
            //disp_round_nav($tid, $_GET['round_id'], false);
            echo "<div id='games'>";
            disp_games($tid);
            echo "</div></div>";
            break;
        case "bracket":
            if (($nrounds > 0) && ($mode == 1)) disp_sglelim($tid);
            break;
        case "wbracket":
            if (($nrounds > 0) && ($mode == 2)) disp_dblelim_wbracket($tid, $nrounds);
            break;
        case "lbracket":
            if (($nrounds > 0) && ($mode == 2)) disp_dblelim_lbracket($tid, $nrounds);
            break;
        case "debug":
            disp_swiss($mid, count($rounds), true);
            break;
        case "results":
            if (count($rounds > 0) && ($mode == 0)) {
                //if (isset($_GET['round_id'])) {
                //    $r = get_tournament_round($tid, $_GET['round_id']);
                //    if ($r)
                //        disp_swiss($tid, $r['round_number'], true);
                //}
                //else
                    disp_swiss($mid, $rounds);
            }
            break;
        case "standings":
            if ($nrounds > 0) disp_places($tid);
            break;
    }
}

function disp_places($tid) {
    $standings = get_standings($tid);
    array_multisort(array_map(function($t) {return $t['rank'];}, $standings), SORT_NUMERIC, 
                    array_map(function($t) {return $t['name'];}, $standings), SORT_STRING,
                    $standings);
    echo "<div class='mainBox'>\n";
    echo "<table class='standings'><th>Rank</th><th>Team</th>";
    foreach ($standings as $t) {
        echo "<tr><td class='numeric'>{$t['rank']}</td><td>{$t['name']}</td></tr>";
    }
    echo "</table>\n</div>\n";
}

function get_td_color($rnum, $pos) {
    $mod = pow(2, $rnum+2);
    $div = pow(2, $rnum+1);
    $color = intval(($pos % $mod) / $div) ? "light" : "dark";
    return $color;
}

function disp_sglelim($tid) {
    $st = get_standings($tid);
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

function disp_dblelim_wbracket($tid, $rnum) {
    $st = get_standings($tid);
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

function disp_dblelim_lbracket($tid, $rnum) {
    $st = get_standings($tid);

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
    echo "<div class='mainBox'>\n";
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
    echo "</table>\n</div>\n";
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

function score_str($result) {  // TODO need team id, then put self first
    $s = $result['score'];
    if ($result['opp_id'] == -1)
        return "BYE";
    else 
        return implode(" - ", $s)." vs {$result['opp_name']}";
}

function disp_swiss($mid, $rounds, $all_breaks = false) {
    $standings = get_standings($rounds[count($rounds)-1], $all_breaks);
    if (count($standings) == 0) { return; } // ay!  when does this happen?
    array_multisort(array_map(function($t) {return $t['rank'];}, $standings), SORT_NUMERIC, $standings);

    echo "<table class='swiss standings'>\n";
    echo "<tr><th>Rank</th><th>Team</th><th colspan=$nrounds>Results</th><th>Total</th><th colspan=3><a href='about.php'>Tie Breaks (in order)</a></th>";
    if ($all_breaks)
        echo "<th>SRS</th><th>Max Likelihood</th><th>Iterated Buchholz</th>";
    echo "</tr>\n";
    echo "<tr><th colspan=2></th>\n";
    for ($i = 1; $i <= count($rounds); $i++)
        echo "<th>R$i</th>";
    echo  "\n<th></th>\n";
    echo  "<th title='Difficulty of all opponents faced'>Buchholz</th><th title='Difficulty of oppoents defeated and drawn'>Berger</th><th title='Integral over time of win/loss score'>Cumulative</th></tr>\n";
    
    foreach ($standings as $rank => $team) {
        echo "<tr>";
        echo "<td class='numeric'>".($rank+1)."</td>";
        echo "<td class='bold'><span title=\"{$team['text']}\">{$team['name']}</span></td>\n";
        for ($i = 0; $i < count($rounds); $i++) {
            echo "<td class='numeric'>";
            // results from round $i
            $results = array_filter($team['results'], function ($r) use ($i) { return $r['rnum'] == $i+1; });
            if (count($results)) {
                $result_str = array_map(function ($r) { return score_str($r); }, $results);
                echo "<span class='result' title=\"".implode("\n",$result_str)."\">";
                echo implode("/", array_map(function ($r) { return $r['res']; }, $results));
                echo "</span>\n";
            }
            echo "</td>\n";
        }
        echo "<td class='bold numeric'>{$team['score']}</td>";
        echo "<td class='numeric'>{$team['buchholz']}</td>";
        echo "<td class='numeric'>{$team['berger']}</td>";
        echo "<td class='numeric'>{$team['cumulative']}</td>";
        if ($all_breaks) {
            echo "<td class='numeric'>".sprintf("%.2f",$team['srs'])."</td>";
            echo "<td class='numeric'>".sprintf("%.4f",$team['maxprob'])."</td>";
            echo "<td class='numeric'>".sprintf("%.4f",$team['iterBuchholz'])."</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}

function disp_scores($team, $disabled="DISABLED") {
    if ($team['score'] < 0) $score = "";
    else                    $score = $team['score'];
    echo "<div class='team'>";
    echo "<span title=\"{$team['team_text']}\">{$team['team_name']}</span>";
    if (($team['team_id'] != -1) && ($team['opp_id'] != -1))
        echo "<br><input type='text' class='short' name='score_{$team['team_id']}' value='$score'} $disabled/>";
    echo "</div>";
}

function disp_score_inputs($team) {
    disp_scores($team, "");
}

function check_team_score($team) {
  return ($team['score'] >= 0);
}

function disp_toggle_button($gid, $tog) {
    $val = $tog ? 'Off Court' : 'On Court';
    echo "<input type='hidden' name='toggle' value='$tog' \>";
    echo "<input class='button' type='button' name='tog_btn' onclick='togOnCourt($gid)' value='$val' \>";
}

function old_disp_game($game, $t, $st) {
    $gid = $game['game_id'];
    $teams = sql_select_all("SELECT * from tblGameTeams JOIN tblTeam using (team_id) WHERE tblGameTeams.game_id = :gid ORDER BY tblGameTeams.score_id DESC", array(":gid" => $gid), $t['db']);
    
    // MIKE TODO IMMEDIATE DEBUG CODE
    /*foreach ($teams as $idx => $score) {
        $teams[$idx]['loser_idx'] = $st[$score['team_id']]['loser_idx'];
        $teams[$idx]['bracket_idx'] = $st[$score['team_id']]['bracket_idx'];
    }*/
    // END DEBUG CODE
    

    if (array_product(array_map('check_team_score', $teams))) {
        $is_finished = true;
        $outer = "played";
    }
    else {
        if ($t['mode'] == 0)  // flag rematches with blue if we're in SWISS mode
            if (game_is_rematch($teams)) $outer = "rematch";
        if ($game['playing'])  $outer .= "playing";
    }

    // if double-elim, add 'losers' designation for losers-bracket games
    if ($t['mode'] == 2) {
        // examine all score differentials from prior rounds, return min
        $query = "SELECT MIN(a.score - b.score) as min
                  FROM tblGameTeams a JOIN tblGameTeams b ON a.game_id = b.game_id
                                      JOIN tblGame      c ON c.game_id = a.game_id
                                      JOIN tblRound     d ON c.round_id = d.round_id
                  WHERE a.team_id = :tid AND b.team_id != :tid AND d.round_number < :rnum AND a.score > -1 AND b.score > -1";
        $is_loser = true;
        foreach ($teams as $x) {
            $q = sql_select_one($query, array(":tid" => $x['team_id'], ":rnum" => $t['rnum'])); 
            $is_loser &= ($q && $q['min'] && ($q['min']<0));  //min=0 [tie] shouldn't ever happen
        }

        $comment = "<span class='disabled'>[".($is_loser ? "L" : "W")."B]</span>";
        if ($is_loser) 
            $outer .= " losers";

    }

    echo "<div id='box_$gid' class='line game $outer'>";
    echo $comment;  // [wb/lb] only when mode=2  TODO: put this somewhere better
    if (count($teams) == 1) {
        $teams[0]['opp_id'] = -1;
        $teams[] = array("team_name" => "BYE", "team_id" => -1);
    }

    if ($t['isadmin']) {
        echo "<form class='$inner' id='form_$gid' name='game_$gid' action='{$t['url']}#box_$gid' method='post' onsubmit='postToggles(this)'>";
        echo "<input type='hidden' name='action' value='' />";
        echo "<input type='hidden' name='game_id' value='$gid' />";
        
        if (is_poweruser())
            disp_tournament_button('Delete', 'delete_game', "this.form.action=\"#edit_round\"");

        // Display the team names and score input boxes
        $stat_ary = array_map('disp_score_inputs', $teams);

        echo "<div class='team'>";
        if ($is_finished)
            disp_tournament_button('Update', 'update_score');
        else {
            disp_toggle_button($gid, $game['playing'] ? "1" : "");
            echo "<br>";
            disp_tournament_button('Set Score', 'update_score');
        }
        echo "</div></form>";
    }
    else 
        $stat_ary = array_map('disp_scores', $teams);

    echo "</div>\n";
}

function disp_oneline($game, $scores) {
    echo "<div class='item'>";

    if (count($scores) == 1)
        echo "Bye for <div class='game-team'>{$scores[0]['team_name']}</div>";
    elseif (count($scores) > 1) {
        $max = $scores[0]; $min = $scores[1];
        if ($scores[1]['score'] > $scores[0]['score']) { $max = $scores[1]; $min = $scores[0]; }
        $victor = "<div class='game-team'><div class='victor'>{$max['team_name']}</div></div>";
        $score = "<span class='score'>{$max['score']}-{$min['score']}</span>";
        $loser = "<div class='game-team'>{$min['team_name']}</div>";
        $normal = "<div class='game-team'>{$max['team_name']}</div> vs. <div class='game-team'>{$min['team_name']}</div>";

        if ($min['score'] == -1) {
            if ($game['court'] > 0) echo "<div class='on-court'><span class='score'>Now playing:<span> $normal</div>";
            else                 echo "<span class='score'>Not yet started:</span> $normal";
        }
        else if ($min['score'] == $max['score'])
            echo "$score Tie, $normal";
        else 
            echo "$score Win, $victor over $loser";
    }
    else
        echo "PROBLEM: display game invoked with no scores";
    echo "</div>";
}

function disp_games($tid) {
    ($db = connect_to_db()) || die("Couldn't connect to database for Display Games");
    $rounds = sql_select_all("SELECT * FROM tblRound WHERE tournament_id = :tid ORDER BY round_id DESC", array(":tid" => $tid), $db);
    $mode = get_tournament_mode($tid);
    if ($rounds) {
        echo "<div class='rounds'>";
        foreach ($rounds as $r) {
            $games = sql_select_all("SELECT * FROM tblGame WHERE round_id = :rid", array(":rid" => $r['round_id']),$db);
            if ($games) {
                echo "<div class='game-box'>";
                if ($mode == 0) echo "Round {$r['round_number']}";
                foreach ($games as $g) {
                    $scores = sql_select_all("SELECT * FROM tblGameTeams a JOIN tblTeam b WHERE a.game_id = :gid AND a.team_id = b.team_id", array(":gid" => $g['game_id']),$db);
                    disp_oneline($g, $scores);
                }
                echo "</div>\n";
            }
        }
        echo "</div>";
    }
}

function disp_round_games($round) {
    $db = connect_to_db();
    $games = get_round_games($round['round_id']);
    if ($games)
        foreach ($games as $g)
            disp_game($g, $db);
}

function disp_game($game) {
    $teams = sql_select_all("SELECT * from tblGameTeams a JOIN tblTeam b using (team_id) WHERE a.game_id = ? ORDER BY a.score_id DESC", array($game['game_id']), $db);
    echo "<div class='game' data-game='".json_encode($game)."' data-score='".json_encode($teams)."'>{$teams[0]['team_name']}";

    if (count($teams) == 1)
        echo " has a BYE";
    else {
        echo " vs {$teams[1]['team_name']}";
        if ($game['status']) {
            echo ", {$teams[0]['score']}-{$teams[1]['score']}";
            if ($game['status'] == 1)  // FINISHED
                echo " Finished";
        }
    }
    echo "</div>";
}

function old_disp_round_games($tid, $aid, $db) {
    $t = array();
    $t['mode']    = get_tournament_mode($tid);
    $t['isadmin'] = $aid && tournament_isadmin($tid, $aid);
    $t['url']     = "play_tournament.php?id=$tid&round_id=$rid";
    $t['db']      = $db;
    $t['rnum']    = $round['round_number'];

    // MIKE DEBUG CODE 
    /* $tmp = get_standings($tid);
    $st = array();
    foreach ($tmp as $st_team)
        $st[$st_team['id']] = $st_team;
    */
    // END DEBUG CODE

    $game_list = sql_select_all("SELECT * FROM tblGame WHERE round_id = :rid", array(":rid" => $rid),$db);
    if ($game_list) {
        foreach ($game_list as $g) {
            $g['playing'] = (strpos($togs, $g['game_id']) > -1);
            disp_game($g, $t, $st);
        }
    }
    return true;
}

function disp_team_select($mid, $name) {
    echo "<select name='$name'>";
    foreach (get_module_teams($mid) as $t)
        echo "<option value='{$t['team_id']}'>{$t['team_name']}</option>";
    echo "<option value='-1'>BYE</option>";
    echo "</select>";
}

function disp_next_round_button($tid, $rid) {
}
?>
