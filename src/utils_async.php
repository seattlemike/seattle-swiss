<?php

function dbAttempt($query, $vars) {
    if (sql_try($query, $vars))
        return array("success" => true);
    else
        return array("errno" => 50, "msg" => "database access failed");
}

function asyncModuleTeamPrivs($mid, $team_id) {
    $module = get_module($mid);
    if (! $module)
        return array("errno" => 20, "msg" => "Couldn't find bracket");
    elseif (! tournament_isadmin($module['parent_id'], $_SESSION['admin_id']))
        return array("errno" => 21, "msg" => "Coudln't find tournament");
    elseif (! tournament_hasteam($module['parent_id'], $team_id))
        return array("errno" => 22, "msg" => "Coudln't find team in tournament");
    else
        return true;
}

function asyncModuleTeamAdd($mid, $tid, $seed) {
    if (is_array($status = asyncModuleTeamPrivs($mid, $tid)))
        return $status;
    elseif (module_hasteam($mid, $tid))
        return array("errno" => 24, "msg" => "Team already present in this module");
    return dbAttempt("INSERT INTO tblModuleTeams (module_id, team_id, team_seed) VALUES (?, ?, ?)", array($mid, $tid, $seed));
}

function asyncModuleTeamDel($mid, $tid) {
    if (is_array($status = asyncModuleTeamPrivs($mid, $tid)))
        return $status;
    elseif (! module_hasteam($mid, $tid))
        return array("errno" => 23, "msg" => "Couldn't find team enabled in this module");
    return dbAttempt("DELETE FROM tblModuleTeams WHERE module_id = ? AND team_id = ?", array($mid, $tid));
}

function processFile($file) {
    // upload error?
    if ($file['error'] != UPLOAD_ERR_OK)
        return array("errno" => 20, "file_err" => $file['error'], "msg" => "problem with file upload");

    // get image specs [height, width, MIMETYPE] 
    $imgAttr = getimagesize($file['tmp_name']);

    // validate filetype
    $allowedTypes = array( 'image/png', 'image/gif', 'image/jpeg' );
    if (! in_array( $imgAttr['mime'], $allowedTypes ))
        return array("errno" => 21, "msg" => "image type not recognized: $type");

    // validate parent_id
    $_POST['entry_id'] = intval($_POST['entry_id']);
    if (! validateEntry($_POST['entry_id']))
        return array("errno" => 100, "msg" => "image parent id validation failed");

    // sanitize filename and path
    $file['name'] = basename($file['name']);
    $file['name'] = strtolower(trim(preg_replace('~[^0-9a-z_.]+~i', '-', $file['name'])));
    $path = "img/{$_POST['entry_id']}/";  //ASSERT $_POST['entry_id'] already sanitized and checked
    
    // validate filename
    $check = sql_select_all("SELECT * FROM tblMedia WHERE parent_id=:pid AND media_filename=:fname",
                        array(":pid" => $_POST['entry_id'], ":fname" => $file['name']));
    if (count($check))
        return array("errno" => 30, "count" => count($check), "msg" => "filename {$file['name']} already exists");
    if (file_exists($path.$file['name']))
        return array("errno" => 31, "msg" => "$path{$file['name']} exists but NOT IN DATABASE");

    if (! is_dir($path))
        if (! mkdir($path, 0755, true))
            return array("errno" => 34, "msg" => "unable to create location to store images: $path");

    // now move file from $file['tmp_name'] to $path/$file['name']
    if (! move_uploaded_file( $file['tmp_name'], "$path{$file['name']}" ))
        return array("errno" => 39, "msg" => "error moving file after upload");

    // add entry to database
    $_POST['text'] = trim($_POST['text']);
    $new_id = sql_insert("INSERT INTO tblMedia (author_id, parent_id, media_path, media_filename, img_width, img_height, media_text) VALUES (:aid, :pid, :path, :fname, :width, :height, :text)", 
                        array(":aid" => $_SESSION['admin_id'], ":pid" => $_POST['entry_id'], ":path" => $path, ":fname" => $file['name'], ":width" => $imgAttr[0], ":height" => $imgAttr[1], ":text" => $_POST['text']));

    return array("entry" => getMedia($new_id), "success" => true);
}

?>
