<?php

namespace App\Pages;

//страница  для  загрузки изображений 
class LoadImage extends \Zippy\Html\WebPage
{

    public function __construct($image_id, $t = null) {
        if (!is_numeric($image_id))
            die;

        $image = \App\Entity\Image::load($image_id);
        if ($image instanceof \App\Entity\Image) {

            header("Content-Type: " . $image->mime);
            if (strlen($t) > 0 && strlen($image->thumb) > 0) {
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
        die;
    }

}
