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

// TODO: some way to identify which page we're on and grey out the appropriate
// menu button.  Use of the global $page_name is a sort-of solution.

//Globals we might be using for our display:
//  $header_text : displayed in topHeader bar
//  $title_text  : goes after 20Swiss in <title>
//  $page_name   : way of id'ing where we are currently

// Someone's server complained until I did this.  lcdradio, I think
ini_set('date.timezone', 'America/Los_Angeles');

require_once("utils_db.php");
require_once("utils_common.php");
require_once("utils_display.php");
require_once("utils_debug.php");
require_once("utils_pairing.php");
require_once("utils_standings.php");

// Buffer all output until footer.php is processed
ob_start();

// Don't start session unless we've already logged in as an Admin
if (isset($_COOKIE[ini_get('session.name')])) {
    session_start();

    // for now, disable timeout
    if (0 && check_login()) {
        // Check that we haven't timed out our login
        if (isset($_SESSION['timeout']) && (max_session_life() < (time() - $_SESSION['timeout'])))
            logout();
        $_SESSION['timeout'] = time();
    }
}

if (isset($_GET['super'])) {
    require_privs(false);
    $title_text = "[super] $title_text";
    $super=true;
}

//if header text is unset, use title_text
if (isset($title_text) && (! isset($header_text)))  { $header_text = $title_text; }

function disp_header($title = null, $js_extra=array(), $header_extra = array()) {
    echo <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
<link href='http://fonts.googleapis.com/css?family=Rosario:400,400italic' rel='stylesheet' type='text/css' />
<link href='http://fonts.googleapis.com/css?family=Rokkitt:700' rel='stylesheet' type='text/css' />
<link rel='stylesheet' type='text/css' href='/style.css' />
END;
    foreach ($js_extra as $jsfile)
        echo "<script src='$jsfile' type='text/javascript'></script>\n";
    foreach ($header_extra as $line)
        echo "$line\n";
    if (! $title)
        $title = "20Swiss";
    else 
        $title = "20Swiss : $title";
    echo "<title>$title</title>\n</head>\n<body>";
}

function disp_admin_topbar() {
    echo "<div class='lHead'>\n";
    echo "<a href='/private/'>{$_SESSION['admin_name']}</a>";
    echo "<a href='/settings/'>settings</a>";
    if ($_SESSION['admin_type'] == 'super') 
        echo "<a href='/super/'>super</a>";
    echo "<a href='/logout/'>log out</a>\n";
    echo "</div>\n";
}

function disp_modnav_topbar($tourney, $module, $page) {
    if (! $tourney)
        return;
    elseif (! $module)
        $navs = array( array( $tourney['tournament_name'], "/private/tournament/{$tourney['tournament_id']}/" ) );
    else 
        $navs = array( array( $tourney['tournament_name'], "/private/tournament/{$tourney['tournament_id']}/" ),
                       array( $module['module_title'], "/private/module/{$module['module_id']}/" ),
                       array( "Run", "/private/module/{$module['module_id']}/run/" ), 
                       array( "Standings", "/private/module/{$module['module_id']}/view/" ));

    echo "<div class='rHead'>";
    foreach ($navs as $idx => $link) {
        list($text, $dest) = $link;
        if ($page == $idx)
            echo "<a class='selected' href='$dest'>$text</a>";
        else 
            echo "<a href='$dest'>$text</a>";
    }
    echo "</div>\n";
}

// assert: already checked tid/mid/has_privs/etc
function disp_topbar($tourney=null, $module=null, $page=null) {
    echo "<div class='topNav nav'>";
    if (check_login()) {
        disp_modnav_topbar($tourney, $module, $page);
        disp_admin_topbar();
    } else {
        disp_modnav_topbar($tourney, $module, $page);
    }
    echo "</div>";
}

//    $tname = get_tournament_name($tid);
function disp_titlebar($title = null) {
    if (! isset($title))
        $title = "20Swiss";
    echo "<h1>$title</h1>";
}

// checks for $tid in POST and GET
function get_tid() {
    if ( isset($_POST['tournament_id']) )
        return $_POST['tournament_id'];
    elseif (isset($_GET['id']))
        return $_GET['id'];
    return null;
}

function get_mid() {
    if (isset($_POST['module_id']))
        return $_POST['module_id'];
    elseif (isset($_GET['module']))
        return $_GET['module'];
    return null;
}

function header_redirect( $dest ) {
    header("location:$dest");
    die();
}


/*
    if (isset($tname)) {  //TODO: should use tournament_exists($tid) instead of isset(tname)
        $title_text = $tname;  // clobbers when we have a tid [desired? used in view.php]
        if ( isset($_POST['round_id']) || isset($_GET['round_id']) )
            ($rid = $_POST['round_id']) || ($rid = $_GET['round_id']);
        if ( (! isset($rid)) || 
             (! get_tournament_round($tid, $rid)) ) {
            $round = get_current_round($tid);
            if (! $round) $rid = -1;
            else          $rid = $round['round_id'];
        }
    }
    else { unset($tid); }
}
*/
?>
