<?php
require_once 'init.php';
  
if(isset($_FILES['upload'])){
   
     $message = '';   
       $imagedata = @getimagesize($_FILES['upload']['tmp_name']);
       if(is_array($imagedata)) {
         
       
         unlink(_ROOT.'/upload/'.$_FILES['upload']['name']);
         move_uploaded_file($_FILES['upload']['tmp_name'],_ROOT.'/upload/'.$_FILES['upload']['name']);
       
            
         $url='/upload/'.$_FILES['upload']['name']; 
        
       } else {
         $message ="Неверное изображение!";  
       }
       
      
     
   echo "<script type='text/javascript'> window.parent.CKEDITOR.tools.callFunction(".$_GET['CKEditorFuncNum'].", '$url', '$message');</script>";
}
