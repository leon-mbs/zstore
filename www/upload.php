<?php
require_once 'init.php';
  
if(isset($_FILES['upload'])){
   
     $message = '';   
       $imagedata = @getimagesize($_FILES['upload']['tmp_name']);
       if(is_array($imagedata)) {
               
    
     
         $filename = basename($_FILES['upload']['name']);
         move_uploaded_file($file["tmp_name"], _ROOT."upload/".$filename);
            
             
             
         $url="/upload/". $filename;
        
       } else {
         $message ="Неверное  изображение!"; 
  
             
       }
      echo "<script type='text/javascript'> window.parent.CKEDITOR.tools.callFunction(".$_GET['CKEditorFuncNum'].", '$url', '$message');</script>";
    
     
}
  