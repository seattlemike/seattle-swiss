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

// insert a record into the db
function sql_insert( $query, $values, $db=null) {
  list($success, , $lastid) = db_execute($query, $values, $db);
  if ($success) return $lastid;
  else          return false;
}

// returns only the top result from a query
function sql_select_one($query, $values, $db=null) {
	try {
    list($success,$stmt,) = db_execute($query, $values, $db);
    if ($success) return $stmt->fetch();
    else          return false;
	}
	catch(PDOException $e) { die ($e->getMessage()); }
}

// returns all records from a query
function sql_select_all($query, $values, $db=null) {
	try {
    list($success,$stmt,) = db_execute($query, $values, $db);
    if ($success) return $stmt->fetchAll();
    else          return false;
	}
	catch(PDOException $e) { die ($e->getMessage()); }
}

?>
