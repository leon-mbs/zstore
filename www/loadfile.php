<?php

require_once 'init.php';

$_REQUEST['id'] = intval($_REQUEST['id']);


$user = \App\System::getUser();
if ($user->user_id == 0) {
    die;
}

$file = \App\Helper::loadFile($_REQUEST['id']);
if ($file == null) {
    die;
}

$size = strlen($file['filedata']);
if ($size > 0) {
    if (strlen($file['mime'] ?? '') > 0 && ($_REQUEST['im'] ?? 0) > 0) {
        header('Content-Type: ' . $file['mime']);
    } else {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $file['filename'] ?? 'file') . '"');
        header('Content-Transfer-Encoding: binary');
    }

    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . $size);

    
    echo $file['filedata'];
    flush();
}
die;
