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


function debug_alert( $str ) {
    echo "<div class='warning'>$str</div>\n";
}

function debug_error( $errnum, $msg, $fname=null) {
    debug_alert("Error $errnum" . ($fname ? " [$fname]" : "") . ": $msg");
    die();
}

function dump_array( $a ) {
    echo "<table>";
    foreach ($a as $k => $v)
        echo "<tr><td>$k</td><td>$v</td></tr>";
    echo "</table>";
}

function debug_phpinfo() {
    phpinfo();
}

function disp_detailed_standings($tid) {
  echo "<table class='standings'>";
  echo "<tr><th>semi-rank</th><th>Rank</th><th>Team Name</th><th>Score</th><th>Buchholz</th><th>Cumulative</th><th>Berger</th>";
  foreach (get_standings($tid) as $rank => $team) {
    echo "<tr>";
    echo "<td>{$team['rank']}</td>";
    echo "<td>".($rank+1)."</td>";
    echo "<td>{$team['name']}</td>";
    echo "<td>{$team['score']}</td>";
    echo "<td>{$team['buchholz']}</td>";
    echo "<td>{$team['cumulative']}</td>";
    echo "<td>{$team['berger']}</td>";
    echo "</tr>";
  }
  echo "</table>";
}

?>
