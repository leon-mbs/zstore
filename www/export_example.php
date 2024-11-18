<?php
/**
* пример  файла  экспорта  ТМЦ
* вызов  с браузера  <адрес сайта> /export.php
*/
 
require_once 'init.php';
 
   $file   ="c:/Users/leonm/Downloads/items.xlsx";//путь  к  файлу
 
 
try{ 
        $data = array();
        $sql = "disabled <> 1 ";  //можно задать дополнительные  условия
        $i = 0;  
        foreach ( \App\Entity\Item::findYield($sql, "itemname asc") as $item) {
            $i++;
            $data['A' . $i] = $item->itemname;
            $data['B' . $i] = $item->shortname;
            //...
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
   
   echo "<br> Импортировано {$cnt} ТМЦ";      
    
} catch (Exception $e) {
    echo $e->getMessage();
    $logger->error($e);
}
