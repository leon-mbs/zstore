<?php
require_once 'init.php';

if (isset($_REQUEST['id']) > 0) {
    $user = \App\System::getUser();
    if ($user->user_id == 0) {
        die;
    }
    $image = \App\Entity\Image::load($_REQUEST['id']);
    if ($image instanceof \App\Entity\Image) {

        header("Content-Type: " . $image->mime);
        if ($_REQUEST['t'] == "t" && strlen($image->thumb) > 0) {
            header("Content-Length: " . strlen($image->thumb));
            echo $image->thumb;
        } else {
            header("Content-Length: " . strlen($image->content));
            echo $image->content;
        }
    } else {

        $file = _ROOT . 'assets/images/noimage.jpg';
        $type = 'image/jpeg';
        header('Content-Type:' . $type);
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }
    exit;

}
