<?php
require_once("utils_settings.php");

// Connect to server and select database
function connect_to_db() {
  try {
    list($db_host, $db_user, $db_pass, $db_name) = get_db_settings();
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);  // connect
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );   // throw exceptions when we do bad things
    return $db;
  }
  catch(PDOException $e) { die ($e->getMessage()); }
}

// Prepare and execute a query
function db_execute( $query, $values, $db=null) {
    if ($db == null) 
        ($db = connect_to_db()) || die("Couldn't connect to database for db_execute");
	try {
        $stmt = $db->prepare($query);
        $success = $stmt->execute($values);
        return array($success, $stmt, $db->lastInsertId());
	}
	catch(PDOException $e) { die ($e->getMessage()); }
}

function sql_try( $query, $values, $db=null) {
  list($success, , ) = db_execute($query, $values, $db);
  return $success;
}

function sql_insert( $query, $values, $db=null) {
  list($success, , $lastid) = db_execute($query, $values, $db);
  if ($success) return $lastid;
  else          return false;
}

function sql_select_one($query, $values, $db=null) {
	try {
    list($success,$stmt,) = db_execute($query, $values, $db);
    if ($success) return $stmt->fetch();
    else          return false;
	}
	catch(PDOException $e) { die ($e->getMessage()); }
}

function sql_select_all($query, $values, $db=null) {
	try {
    list($success,$stmt,) = db_execute($query, $values, $db);
    if ($success) return $stmt->fetchAll();
    else          return false;
	}
	catch(PDOException $e) { die ($e->getMessage()); }
}

//MIKE:  not yet sure when these are used

// returns true if field is empty
function check_for_empty_field($field) {
    $empty = true;
    if ($field != '') {
        $empty = false;
    }
    return $empty;
}

// checks array for empty variables
function check_array_for_empty_vars($vars) {
    $empty_fields = false;
    $number_of_vars = count($vars);
    $i = 0;
    while (!$empty_fields && $i < $number_of_vars) {
        if (check_for_empty_field($vars[$i])) {
            $empty_fields = true;
        }
        $i++;
    }
    return $empty_fields;
}

//MIKE:  **DEPRECATED**

// execute sql string and return the result
function execute_sql($sql, $connection) {
    $result = mysql_query($sql, $connection) or die('Error with: ' . $sql . "<br>" . mysql_error());
    return $result;
}

// sanitizes input for safely storing in db
function sanitize($var) {
    $var = trim($var);
    $var = stripslashes($var);
    mysql_real_escape_string($var);
    return $var;
}

// converts date format to be stored in db
function date_to_db($date) {
    $date = substr($date, 6, 4) . substr($date, 0, 2) . substr($date, 3, 2);
    return $date;
}

// converts date from db to date format
function db_to_date($date) {
    //$date = substr($date, 4, 2) . "/" . substr($date, 6, 2) . "/" . substr($date, 0, 4);
    return date("m/d/Y", strtotime($date));
}

?>
