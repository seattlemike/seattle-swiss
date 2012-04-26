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

    $modes = array("Swiss Rounds", "Single Elimination");
    $def = "class='wide' type='text' maxlength='40'";
    $inputs = array("Name" => "<input $def name='tournament_name' value='{$tourney['tournament_name']}' />",
                    "City" => "<input $def name='tournament_city' value='{$tourney['tournament_city']}' />",
                    "Date" => "<input $def name='tournament_date' value='$date' />", 
                    "Mode" => "<input $def name='tournament_mode' value='{$modes[$tourney['tournament_mode']]}' DISABLED />",
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
    if ((! $tlist) || (count($tlist) == 0))
        echo "<div class='header'>[no tournaments yet]</div>\n";
    else {
        foreach ($tlist as $tourney) {
            if ($tourney['tournamnent_owner'] == $aid) { $class = 'owner'; }
            else                                       { $class = ''; }
            echo "<div class='line'>\n";
            echo "<a class='$class button' href='$dest?id={$tourney['tournament_id']}'>{$tourney['tournament_name']}</a>";
            echo "</div>\n";
        }
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

function disp_team_edit($team) {
    $tid = $team['team_id'];

    echo "<tr><td>\n";
    disp_tournament_button("Update", "update_team", "this.form.elements[\"team_id\"].value=$tid;");
    echo "</td>\n<td>";
    disp_tournament_button("Disable", "disable_team", "this.form.elements[\"team_id\"].value=$tid;",
        $team['is_disabled'] ? "selected" : "");

    if (!$team['team_uid'])   { $team['team_uid'] = ""; }
    if (!$team['team_init'])  { $team['team_init'] = ""; }
    echo "</td>\n";
    echo "<td><input type='text' name='name_$tid' value='{$team['team_name']}'></td>\n";
    echo "<td><input type='text' name='text_$tid' value='{$team['team_text']}'></td>\n";
    echo "<td><input class='numeric' type='text' name='uid_$tid' value='{$team['team_uid']}'></td>\n";
    echo "<td><input class='numeric' type='text' name='init_$tid' value='{$team['team_init']}'></td>\n";
    echo "</tr>\n";
}

function disp_round_nav($tid, $rid, $aid=null) {
  if ($aid) $isadmin = tournament_isadmin($tid, $aid);
  //if ($isadmin) $url = "play_tournament.php";
  //else          $url = "view_games.php";

  $rounds = sql_select_all("SELECT * FROM tblRound WHERE tournament_id = :tid ORDER BY round_number",
                           array(":tid" => $tid));
  if ( $rounds != false ) {
    foreach ($rounds as $r) {
      if ($r['round_id'] == $rid) $class = "selected";
      else                        $class = "";
      echo "<a class='button $class' href='play_tournament.php?id=$tid&round_id={$r['round_id']}'>";
      echo "Round {$r['round_number']}</a>";
    }
    if ($isadmin) disp_tournament_button("+", "add_round"); 
  }
  else {
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
        echo "<div class='header playing'>Tournament not yet started</div>\n";
        disp_teams_list($tid);
    }
    else {
        if (tournament_is_over($tid)) $title = "Final Standings";
        else                       $title = "Standings [round $nrounds]";
        echo "<div class='header'>$title</div>\n";
        $mode = get_tournament_mode($tid);
        if ($mode == 0)
            disp_swiss($tid, $nrounds);
        elseif ($mode == 1)
            disp_elim($tid, $nrounds);
    }
}

function disp_color_td($inner, $rnum, $pos) {
    $mod = pow(2, $rnum+2);
    $div = pow(2, $rnum+1);
    $color = intval(($pos % $mod) / $div) ? "light" : "dark";
    echo "<td class='$color'>$inner</td>\n";
}
// standings are BEST TO WORST
function disp_elim($tid) {
    $standings = get_standings($tid);
    $nrounds = ceil(log(count($standings),2));
    $bsize = pow(2, $nrounds);

    echo "<table class='elim standings'>\n";
    echo "<tr><th>Seed</th><th>Place</th><th colspan=".(2*$nrounds+1).">Results</th></tr>\n";
    echo "<tr><th colspan='".(2*$nrounds+3)."' class='blank'>&nbsp;</th></tr>";
    // Pad table with 'blank's up to bsize for nice pow2 display
    $blank = array( 'result' => array() );
    foreach (range(0, $bsize-1) as $pos) {
        if ($standings[$pos-$del]['pos'] != $pos) {
            $table[] = $blank;
            $del++;
        } else
            $table[] = $standings[$pos-$del];
    }
    foreach ($table as $idx => $team) {
        echo "<tr>";
        echo "<td>{$team['seed']}</td>\n";
        echo "<td>{$team['rank']}</td>\n";
        disp_color_td("<span title='{$team['text']}'>{$team['name']}</span>", 0, $idx);
        for ($i = 0; $i < $nrounds; $i++) {
            echo "<td style='min-width:5px;'></td>";  //spacer
            if ($team['result'][$i]) $inner = "<span title='{$team['text']}'>{$team['name']}</span>";
            else                       $inner = "";
            disp_color_td($inner, $i+1, $idx);
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}


function disp_swiss($tid, $nrounds) {
    $standings = get_standings($tid);
    if (count($standings) == 0) { return; }

    echo "<table class='swiss standings'>\n";
    echo "<tr><th>Rank</th><th>Team</th><th colspan=$nrounds>Results</th><th>Total</th><th colspan=3>Tie Breaks</th></tr>\n";
    echo "<tr><th colspan=2></th>\n";
    for ($i = 1; $i <= $nrounds; $i++)
        echo "<th>R$i</th>";
    echo  "\n<th></th>\n";
    echo  "<th title='Difficulty of all opponents faced'>Buchholz</th><th title='Difficulty of oppoents defeated and drawn'>Berger</th><th title='Integral over score across rounds'>Cumulative</th></tr>\n";
    
    foreach ($standings as $rank => $team) {
        echo "<tr>";
        echo "<td>".($rank+1)."</td>";
        echo "<td class='bold'><span title='{$team['text']}'>{$team['name']}</span></td>\n";
        for ($i = 0; $i < $nrounds; $i++)
            echo "<td><span class='result' title='vs {$team['opp_name'][$i]}'>{$team['result'][$i]}</span></td>\n";
        echo "<td class='bold'>{$team['score']}</td>";
        echo "<td>{$team['buchholz']}</td>";
        echo "<td>{$team['berger']}</td>";
        echo "<td>{$team['cumulative']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
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

function disp_game($game, $url, $isadmin, $db = null) {
    $gid = $game['game_id'];
    $teams = sql_select_all("SELECT * from tblGameTeams JOIN tblTeam using (team_id) WHERE tblGameTeams.game_id = :gid", array(":gid" => $gid), $db);

    if (array_product(array_map('check_team_score', $teams)))
        $outer = "played";
    else {
        if (game_is_rematch($teams)) $outer = "rematch";
        if ($game['playing'])        $inner = "playing";
    }

    echo "<div class='line game $outer'>";
    echo "<form class='$inner' id='game_$gid' name='game_$gid' action='$url' method='post' onsubmit='postToggles(this)'>";
    echo "<input type='hidden' name='action' value='' />";
    echo "<input type='hidden' name='game_id' value='$gid' />";
    if ($isadmin)
        disp_tournament_button('Delete', 'delete_game');

    $stat_ary = array_map('disp_team_score', $teams);
    if (count($teams) == 1) // this game is a BYE
      echo "<div class='team'>BYE</div>";

    if ($isadmin) {
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
    if (! get_tournament_round($tid, $rid))
        return false;

    $isadmin = $aid && tournament_isadmin($tid, $aid);
    $url     = "play_tournament.php?id=$tid&round_id=$rid";

    $game_list = sql_select_all("SELECT * FROM tblGame WHERE round_id = :rid", array(":rid" => $rid),$db);
    if ($game_list) {
        foreach ($game_list as $g) {
            $g['playing'] = (strpos($togs, $g['game_id']) > -1);
            disp_game($g, $url, $isadmin, $db);
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
