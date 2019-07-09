<?php
require_once 'init.php';
  
if(isset($_FILES['upload'])){
   
       $message = '';   
       $imagedata = @getimagesize($_FILES['upload']['tmp_name']);
       if(is_array($imagedata)) {
         $filename = basename($_FILES['upload']['name']);      
         if($_REQUEST['m'] == "note") {  //модуль базы знаний   
     
            $image = new \App\Entity\Image();
            $image->content = file_get_contents($_FILES['upload']['tmp_name']);
            $image->mime = $imagedata['mime'];
  
            $image->save();      
         
            $url="/loadimage.php?id=". $image->image_id;
                
         }
         else {
           move_uploaded_file($file["tmp_name"], _ROOT."upload/".$filename);
           $url="/upload/". $filename;
             
         }
         
        
       } else {
         $message ="Неверное  изображение!"; 
  
             
       }
      echo "<script type='text/javascript'> window.parent.CKEDITOR.tools.callFunction(".$_GET['CKEditorFuncNum'].", '$url', '$message');</script>";
    
     
}
  