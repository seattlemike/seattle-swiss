<?php
    $page_name = "Standings";
    $title_text = "Tournaments";
    include("header.php");

	// get tournament data
	($tid = $_GET['id']) || ($tid = $_POST['tournament_id']);

    echo "<div class='con'>";
    echo "<div class='centerBox'>";
    echo "<div class='mainBox'>";
    if (!$tid) {
        //List of public tournaments?
        $tlist = sql_select_all("SELECT * FROM tblTournament WHERE is_public = 1", array());
        disp_tournaments($tlist, 'view.php');
    }
    else {   
        disp_standings($tid, $_SESSION['admin_id']);
    }
    echo "\t</div>\n</div>\n</div>\n";

include("footer.php"); 
?>
