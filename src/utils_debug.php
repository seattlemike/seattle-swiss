<?php

function dump_array( $a ) {
  echo "<table>";
  foreach ($a as $k => $v)
    echo "<tr><td>$k</td><td>$v</td></tr>";
  echo "</table>";
}

function debug_alert( $str ) {
  echo "<h3>$str</h3><br>\n";
}

function debug_phpinfo() {
    phpinfo();
}
?>
