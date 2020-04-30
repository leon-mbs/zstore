<?php
require_once 'init.php';

if (isset($_FILES['upload'])) {

    $message = '';
    $imagedata = @getimagesize($_FILES['upload']['tmp_name']);
    if (is_array($imagedata)) {
        $filename = basename($_FILES['upload']['name']);
        if ($_REQUEST['db'] == "true") {  //запись  в БД

            $image = new \App\Entity\Image();
            $image->content = file_get_contents($_FILES['upload']['tmp_name']);
            $image->mime = $imagedata['mime'];

            $image->save();

            $url = "/loadimage.php?id=" . $image->image_id;

        } else {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $filename = md5(time() . $filename) . '.' . $ext;

            move_uploaded_file($_FILES['upload']["tmp_name"], _ROOT . "upload/" . $filename);
            $url = "/upload/" . $filename;

        }


    } else {
        $message = "Неверное  изображение!";


    }
    echo "<script type='text/javascript'> window.parent.CKEDITOR.tools.callFunction(" . $_GET['CKEditorFuncNum'] . ", '$url', '$message');</script>";


}
  