<?php
/**
* пример  файла  экспорта  ТМЦ
* вызов  с браузера  <адрес сайта> /export.php
*/
 
 
 die;// убрать для  работы

 \App\System::checkIP()  ;  //проверка  IP (задается  на станице  администратора)
 
require_once 'init.php';
 
   $file   ="";//путь  к  файлу
 
 
try{ 
        $data = array();
        $sql = "disabled <> 1 ";  //можно задать дополнительные  условия
        $i = 0;  
        foreach ( \App\Entity\Item::findYield($sql, "itemname asc") as $item) {
            $i++;
            $data['A' . $i] = $item->itemname;
            $data['B' . $i] = $item->shortname;
            //...
           
              кастомные поля
              $cf= $item->getcf() ;
              $data['H' . $i] = $cf['код поля']->val;
           
        } 
        
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        foreach($data as $k => $v) {

                $c = $sheet->getCell($k);
                $c->setValue($v);
                $c->setValueExplicit($v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            
        }   
        
   $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

   $writer->save($file);  
   
   echo "<br> Экспортовано  {$cnt} ТМЦ";      
    
} catch (Exception $e) {
    echo $e->getMessage();
    $logger->error($e);
}
 