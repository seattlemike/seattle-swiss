<?php 

// TODO: some way to identify which page we're on and grey out the appropriate
// menu button.  Use of the global $page_name is a sort-of solution.

//Globals we might be using for our display:
//  $header_text : displayed in topHeader bar
//  $title_text  : goes after Seattle Swiss in <title>
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

    if (check_login()) {
        // Check that we haven't timed out our login
        if (isset($_SESSION['timeout']) && (max_session_life() < (time() - $_SESSION['timeout'])))
            logout();
        $_SESSION['timeout'] = time();
    }
}

// check for tournament_id and round_id
if ( isset($_POST['tournament_id']) || isset($_GET['id']) ) {
    ($tid = $_POST['tournament_id']) || ($tid = $_GET['id']);

    $tname = get_tournament_name($tid);
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

if (isset($_GET['super'])) {
    require_privs(false);
    $title_text = "[super] $title_text";
    $super=true;
}

//if header text is unset, use title_text
if (isset($title_text) && (! isset($header_text)))  { $header_text = $title_text; }

?>


<html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <link href='http://fonts.googleapis.com/css?family=Rosario' rel='stylesheet' type='text/css'>
        <link href='http://fonts.googleapis.com/css?family=Rokkitt:700' rel='stylesheet' type='text/css'>
        <link rel='stylesheet' type='text/css' href='style.css' />
        <script src="swiss.js" type="text/javascript"></script>
        <?php 
            $title = "Seattle Swiss";
            if (isset($title_text)) { $title .= " : $title_text"; }
        ?>
        <title><?echo $title;?></title>  
    </head>
    <body>
        <div class='backHead nav'>
            <?php 
                disp_header_tnav($tid, $tname, $page_name);
                disp_header_admin();
            ?>
        </div>
        <div class='topHeader'> <? echo $header_text; ?> </div>

