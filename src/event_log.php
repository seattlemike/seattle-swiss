<?php
/*
    Copyright 2013 Mike Bell and Paul Danos

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
	require_login();
    require_privs(false);  // requre SUPER
    $title = "Event Log";

    $js_extra = array("/ui.js");
    disp_header($title, $js_extra);
    disp_topbar();
    disp_titlebar($title);

    function disp_event($e) {
        // format date, admin name
        $types = array( LOG_ACTION_LOGIN => "Login",
                        LOG_ACTION_LOGOUT => "Logout",
                        LOG_ACTION_NEWACCOUNT => "Created New Account",
                        LOG_ACTION_NEWTOURNEY => "New Tournament",
                        LOG_ACTION_DELTOURNEY => "Delete Tournament", );

        $date = date("H:i:s", strtotime($e['log_time']));
        // Date
        echo "<div class='event'><div class='date'>$date</div>";
        // Admin Name
        echo "<div class='admin'><a href='/log/admin/{$e['admin_id']}/'>{$e['admin_name']}</a></div>";

        echo "<div class='text'>";
        switch ($e['log_action']) {
            case LOG_ACTION_NEWTOURNEY:
            case LOG_ACTION_DELTOURNEY:
                echo "{$types[$e['log_action']]} [{$e['log_note']}]";
                break;
            default:
                echo $types[$e['log_action']];
        }
        echo "</div></div>";
    }

    echo "<div class='con'> <div class='centerBox'> <div class='mainBox'>\n";
    // SHOULD BE ABLE TO FILTER EVENTS BY:
    //    date range
    //    admin
    //    event type
    //    tid/mid(?)
    $list = get_events();
    /*  Do we want the log to include deleted admins?
    $list = sql_select_all("SELECT * FROM tblSystemLog ORDER BY log_time DESC", array());
    $admin_data = sql_select_all("SELECT admin_id, admin_name FROM tblAdmin", array());
    foreach ($admin_data as $a)
        $admins[$a['admin_id']] = $a;

    foreach ($list as $e) {
        $date = date("Ymd",strtotime($e['log_time']));
        if (! $admins[$e['admin_id']])
            $admins[$e['admin_id']] = array("admin_name" => "[Deleted]");
        echo "<div>$date  {$admins[$e['admin_id']]['admin_name']}: {$e['log_action']}</div>";
    }
    die();
    */

    // group by days?
    $days = array();
    foreach ($list as $e) {
        //$date = intval((strtotime($e['log_time'])-3600*8) / 86400);
        $date = date("Ymd",strtotime($e['log_time']));
        $days[$date][] = $e;
    }
    foreach ($days as $day) {
        echo "<div class='header'>".date("M d, Y", strtotime($day[0]['log_time']))."</div>";
        echo "<div class='games-list'>";
        foreach ($day as $e)
            disp_event($e);
        echo "</div>";
    }
    echo "</div></div></div>\n";
    include("footer.php"); 
?>
