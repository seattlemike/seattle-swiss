<?php
    $page_name = "Standings";
    $title_text = "Tournaments";
    include("header.php");
?>

<div class='con'>
    <div class='centerBox'>
        <div class='mainBox'>
            <?php 
                ($tid = $_GET['id']) || ($tid = $_POST['tournament_id']);
                if ($tid) {
                    if ((tournament_ispublic($tid)) || 
                        (check_login() && tournament_isadmin($tid, $_SESSION['admin_id'])))
                        disp_standings($tid);
                    else
                        header("location:view.php");
                }
                else {  // List of public tournaments
                    $tlist = sql_select_all("SELECT * FROM tblTournament WHERE is_public = 1 ORDER BY tournament_date DESC", array());
                    disp_tournaments($tlist, 'view.php');
                }
            ?>
        </div>
    </div>
</div>
<? include("footer.php"); ?>
