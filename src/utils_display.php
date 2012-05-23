<?php

//
//  Disp utilities.  Should sort these a bit.
//

//
// **GENERIC**
//

// button with onclick that modifies form.action
function disp_tournament_button($value, $action, $extra='', $class='') {
    $onclick = "this.form.elements[\"action\"].value=\"$action\"; $extra";
    echo "<input onclick='$onclick' class='button $class' type='submit' name='submit' value='$value' />";
}

//
//  **HEADER**
//

function disp_header_admin() {
    if (check_login()) {
        echo "<div class='lHead'>\n";
        echo "<a href='main_menu.php'>{$_SESSION['admin_name']}</a>";
        echo "<a href='user.php'>settings</a>";
        if ($_SESSION['admin_type'] == 'super') 
            echo "<a href='main_menu.php?super=true'>super</a>";
        echo "<a href='logout.php'>log out</a>\n";
        echo "</div>\n";
    }
}

function disp_header_tnav($tid, $tname, $pagename='') {
    $navs = array( "Edit" => "tournament.php", "Run" => "play_tournament.php", "Standings" => "view.php");
    if (check_login() && isset($tid)) {
        echo "<div class='rHead'>$tname\n";
        foreach ($navs as $name => $dest) {
            if ($pagename == $name) { $class = "class='selected'"; }
            else                    { $class = ""; }
            echo "<a $class href='$dest?id=$tid'>$name</a>";
        }
        echo "</div>\n";
    }
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
        <div class='header'>Status</div>
        <p><? echo $status; ?></p>
    </div>
    <?
}

function disp_tournament_details($tourney = null) {

    if (isset($tourney))   $date = date("m/d/Y", strtotime($tourney['tournament_date']));
    else                   $date = date("m/d/Y");

    if ($tourney['is_public']) $ispublic="checked='checked'";
    if ($tourney['is_over']) $isover="checked='checked'";

    $modes = array("Swiss Rounds", "Single Elimination", "Double Elimination");
    $def = "class='wide' type='text' maxlength='40'";
    $inputs = array("Name" => "<input $def name='tournament_name' value=\"{$tourney['tournament_name']}\" />",
                    "City" => "<input $def name='tournament_city' value=\"{$tourney['tournament_city']}\" />",
                    "Date" => "<input $def name='tournament_date' value='$date' />", 
                    "Mode" => "<input $def name='tournament_mode' value=\"{$modes[$tourney['tournament_mode']]}\" DISABLED />",
                    "Public" => "<input type='checkbox' name='is_public' value='public' $ispublic />",
                    "Finished" => "<input type='checkbox' name='is_over' value='finished' $isover />");
                    
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

function disp_tournaments($tlist, $dest='tournament.php') {
    echo "<div class='mainBox'>\n";
    $mode = array("Swiss", "Single Elim", "Double Elim");
    if ((! $tlist) || (count($tlist) == 0))
        echo "<div class='header'>[no tournaments yet]</div>\n";
    else {
        echo "<table>\n";
        foreach ($tlist as $tourney) {
            echo "<tr>\n";
            $date = date("M d, Y", strtotime($tourney['tournament_date']));
            echo "<td>$date</td><td>{$mode[$tourney['tournament_mode']]}</td>\n";
            // if ($tourney['tournamnent_owner'] == $aid) { $class = 'owner'; }
            // else                                       { $class = ''; }
            //echo "<div class='line'>\n";
            echo "<td class='btnCtr'>\n";
            echo "<a href='/rss/{$tourney['tournament_id']}/' title='Subscribe to Tournament RSS Feed'><img src='/img/feed-icon-28x28.png' width='14px' height='14px' /></a>\n";
            echo "</td>\n";
            echo "<td><a href='$dest?id={$tourney['tournament_id']}'>{$tourney['tournament_name']}</a></td>";
            echo "</tr>\n";
            //echo "</div>\n";
        }
        echo "</table>\n";
    }
    echo "</div>\n";
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
function disp_round_nav($tid, $rid, $aid=null) {
  if ($aid) $isadmin = tournament_isadmin($tid, $aid);
  //if ($isadmin) $url = "play_tournament.php";
  //else          $url = "view_games.php";

  $rounds = sql_select_all("SELECT * FROM tblRound WHERE tournament_id = :tid ORDER BY round_number",
                           array(":tid" => $tid));
  if ( $rounds != false ) { // if rounds exist, show nav buttons for them
    foreach ($rounds as $r) {
      if ($r['round_id'] == $rid) $class = "selected";
      else                        $class = "";
      echo "<a class='button $class' href='play_tournament.php?id=$tid&round_id={$r['round_id']}'>";
      echo "Round {$r['round_number']}</a>";
    }
    if ($isadmin) disp_tournament_button("+", "add_round"); 
  }
  else { // if rounds don't exist, show nav button to start 1st round
    echo "<div class='line'>";
    if ($isadmin) disp_tournament_button("Start","populate_round");
    echo "</div>\n";
  }
}

function disp_teams_list($tid) {
  //TODO: grey out teams that have been disabled
  //TODO: if team_init is nonzero, display this somewhere
  echo "<div class='header'>Teams</div>\n";
  $teams = sql_select_all("SELECT * FROM tblTeam WHERE tournament_id = :tid ORDER BY team_name ASC", array(":tid" => $tid));
  if (!$teams) return;
  echo "<div class='cbox'>\n";
  foreach ($teams as $t) {
    echo "<p>{$t['team_name']} : <i>{$t['team_text']}</i></p>\n";
  }
  echo "</div>\n";
}

//
// **STANDINGS**
//

function disp_standings($tid) {
    $nrounds = get_tournament_nrounds($tid);

    if ($nrounds == 0) {
        echo "<div class='mainBox'>\n";
        echo "<div class='header playing'>Tournament not yet started</div>\n";
        disp_teams_list($tid);
        echo "</div>\n";
    }
    else {
        if (tournament_is_over($tid)) $title = "Final Standings";
        else                       $title = "Standings [round $nrounds]";
        echo "<div class='header'>$title</div>\n";
        $mode = get_tournament_mode($tid);
        switch ($mode) {
            case 0:
                disp_swiss($tid, $nrounds);
                break;
            case 1:
                disp_elim($tid);
                disp_places($tid);
                break;
            default:
                disp_places($tid);
        }
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

function disp_color_td($team, $round, $y) {
    $mod = pow(2, $round+2);
    $div = pow(2, $round+1);
    $color = intval(($y % $mod) / $div) ? "light" : "dark";

    echo "<td class='$color'>";
    echo "<div class='result' title='{$team['text']}'>{$team['name']}</div>";
    echo "</td>\n";
    echo "<td class='$color numeric'>";
    if (($team['opponents'][$round] != -1) && ($team['id'] != -1))   // Game is not a BYE
        echo "<div class='score'>{$team['games'][$round][$team['id']]}</div>";
    echo "</td>\n";
}


// standings are BEST TO WORST
function disp_elim($tid) {
    $standings = get_standings($tid);

    array_multisort(array_map(function($t) {return $t['bracket_idx'];}, $standings), SORT_NUMERIC, $standings);
    $nrounds = ceil(log(count($standings),2));
    $bsize = pow(2, $nrounds);

    echo "<div class='mainBox'>\n";
    echo "<table class='elim standings'>\n";
    echo "<tr><th>Seed</th><th colspan=".(3*$nrounds+2).">Results</th></tr>\n";
    echo "<tr><th colspan='".(3*$nrounds+3)."'> &nbsp;</th></tr>";
    // Pad table with 'blank's up to bsize for nice pow2 display
    $bye = array( 'id' => -1, 'name' => 'BYE', 'result' => array() );
    $blank = array( 'id' => -1, 'result' => array() );
    foreach (range(0, $bsize-1) as $pos) {
        if ($standings[$pos-$del]['bracket_idx'] != $pos) {
            $table[] = $bye;
            $del++;
        } else
            $table[] = $standings[$pos-$del];
    }
    foreach ($table as $idx => $team) {
        echo "<tr>";
        echo "<td class='numeric'>{$team['seed']}</td>\n";

        disp_color_td($team, 0, $idx);
        for ($i = 0; $i < $nrounds; $i++) {
            echo "<td class='spacer'></td>";  //spacer
            if ($team['result'][$i]) disp_color_td($team, $i+1, $idx);
            else                     disp_color_td($blank, $i+1, $idx);
        }
        echo "</tr>\n";
    }
    echo "</table>\n</div>\n";
}

function score_str($result) {  // TODO need team id, then put self first
    $s = $result['score'];
    if (count($s) > 1)
        return implode(" - ", $s)." vs {$result['opp_name']}";
    else 
        return "BYE";
}

function disp_swiss($tid, $nrounds) {
    //MIKE TODO IMMEDIATE:  make sure results actually sit in the round in which they occur
    //  what if a team plays twice in a round?  
    //     then: highlight and display the full results in title-text
    $standings = get_standings($tid);
    if (count($standings) == 0) { return; }
    array_multisort(array_map(function($t) {return $t['rank'];}, $standings), SORT_NUMERIC, $standings);

    echo "<div class='mainBox'>\n";
    echo "<table class='swiss standings'>\n";
    echo "<tr><th>Rank</th><th>Team</th><th colspan=$nrounds>Results</th><th>Total</th><th colspan=3><a href='about.php'>Tie Breaks (in order)</a></th></tr>\n";
    echo "<tr><th colspan=2></th>\n";
    for ($i = 1; $i <= $nrounds; $i++)
        echo "<th>R$i</th>";
    echo  "\n<th></th>\n";
    echo  "<th title='Difficulty of all opponents faced'>Buchholz</th><th title='Difficulty of oppoents defeated and drawn'>Berger</th><th title='Integral over time of current win/loss score'>Cumulative</th></tr>\n";
    
    foreach ($standings as $rank => $team) {
        echo "<tr>";
        echo "<td class='numeric'>".($rank+1)."</td>";
        echo "<td class='bold'><span title='{$team['text']}'>{$team['name']}</span></td>\n";
        for ($i = 0; $i < $nrounds; $i++) {
            echo "<td class='numeric'>";
            // results from round $i
            $results = array_filter($team['results'], function ($r) use ($i) { return $r['rnum'] == $i+1; });
            if (count($results)) {
                $result_str = array_map(function ($r) { return score_str($r); }, $results);
                echo "<span class='result' title='".implode("\n",$result_str)."'>";
                echo implode("/", array_map(function ($r) { return $r['res']; }, $results));
                echo "</span>\n";
            }
            echo "</td>\n";
        }
        echo "<td class='bold numeric'>{$team['score']}</td>";
        echo "<td class='numeric'>{$team['buchholz']}</td>";
        echo "<td class='numeric'>{$team['berger']}</td>";
        echo "<td class='numeric'>{$team['cumulative']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n</div>\n";
}

function disp_team_score($team) {
    if ($team['score'] < 0)
        $score = "";
    else
        $score = $team['score'];

    echo "<div class='team'>";
    echo "<span title='{$team['team_text']}'>{$team['team_name']}</span>\n";
    echo "<br><input type='text' class='short' name='score_{$team['team_id']}' value='$score'} />";
    echo "</div>";

    return ($score != "");
}

function check_team_score($team) {
  return ($team['score'] >= 0);
}

function disp_toggle_button($gid, $tog) {
    $val = $tog ? 'Off Court' : 'On Court';
    echo "<input type='hidden' name='toggle' value='$tog' \>";
    echo "<input class='button' type='button' name='tog_btn' onclick='togOnCourt($gid)' value='$val' \>";
}

function disp_game($game, $t) {
    $gid = $game['game_id'];
    $teams = sql_select_all("SELECT * from tblGameTeams JOIN tblTeam using (team_id) WHERE tblGameTeams.game_id = :gid", array(":gid" => $gid), $t['db']);

    if (array_product(array_map('check_team_score', $teams)))
        $outer = "played";
    else {
        if ($t['mode'] == 0)  // flag rematches with blue if we're in SWISS mode
            if (game_is_rematch($teams)) $outer = "rematch";
        if ($game['playing'])  $inner = "playing";
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
        if ($is_loser)
            $outer .= " losers";
    }

    echo "<div class='line game $outer'>";
    echo "<form class='$inner' id='game_$gid' name='game_$gid' action='{$t['url']}' method='post' onsubmit='postToggles(this)'>";
    echo "<input type='hidden' name='action' value='' />";
    echo "<input type='hidden' name='game_id' value='$gid' />";
    if ($t['isadmin'])
        disp_tournament_button('Delete', 'delete_game');

    $stat_ary = array_map('disp_team_score', $teams);
    if (count($teams) == 1) // this game is a BYE
      echo "<div class='team'>BYE</div>";

    if ($t['isadmin']) {
        echo "<div class='team'>";
        if ($outer == 'played')
            disp_tournament_button('Update', 'update_score');
        else {
            disp_toggle_button($gid, $game['playing'] ? "1" : "");
            echo "<br>";
            disp_tournament_button('Set Score', 'update_score');
        }
        echo "</div>";
    }
    echo "</form>\n</div>\n";
}

function disp_games($togs, $tid, $rid, $aid=null) {
    ($db = connect_to_db()) || die("Couldn't connect to database for disp_games");
    $round = get_tournament_round($tid, $rid);
    if (! $round)
        return false;
    
    $t = array();
    $t['mode']    = get_tournament_mode($tid);
    $t['isadmin'] = $aid && tournament_isadmin($tid, $aid);
    $t['url']     = "play_tournament.php?id=$tid&round_id=$rid";
    $t['db']      = $db;
    $t['rnum']    = $round['round_number'];

    $game_list = sql_select_all("SELECT * FROM tblGame WHERE round_id = :rid", array(":rid" => $rid),$db);
    if ($game_list) {
        foreach ($game_list as $g) {
            $g['playing'] = (strpos($togs, $g['game_id']) > -1);
            disp_game($g, $t);
        }
    }
    return true;
}

function disp_team_select($tid, $name) {
    echo "<select name='$name'>";
    $teams = array_filter(get_tournament_teams($tid), function ($t) {return (!$t['is_disabled']);});
    foreach ($teams as $t)
        echo "<option value='{$t['team_id']}'>{$t['team_name']}</option>";
    echo "<option value='-1'>BYE</option>";
    echo "</select>";
}

function disp_next_round_button($tid, $rid) {
  $next_round = tournament_next_round($rid);
  if ((! $next_round) || (tournament_round_is_empty($next_round['round_id']))) {
    $onclick = " this.form.elements[\"populate_id\"].value=\"{$next_round['round_id']}\";";
    disp_tournament_button("Run Next Round", "populate_round", $onclick);
  }
}
?>
