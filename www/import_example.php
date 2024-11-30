<?php
/**
* пример  файла  импорта  ТМЦ
* вызов  с браузера  <адрес сайта> /import.php
*/
 
require_once 'init.php';
 
 die;// убрать для  работы

 \App\System::checkIP()  ;
 
try{              
        $file   ="";//путь  к  файлу
        $data = array();
        //загрузка  с  ексель
        $oSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file ); 
        $oCells = $oSpreadsheet->getActiveSheet()->getCellCollection();

        for ($iRow = 1; $iRow <= $oCells->getHighestRow(); $iRow++) {

            $row = array();
            for ($iCol = 'A'; $iCol <= $oCells->getHighestColumn(); $iCol++) {
                $oCell = $oCells->get($iCol . $iRow);
                if ($oCell) {
                    $row[$iCol] = $oCell->getValue();
                }   
            }
            $data[$iRow] = $row; 
        }

        unset($oSpreadsheet);    
    
        if($_GET['preview']=="true")  {  //   /import.php?preview=true
            foreach($data as $row) {
              print_r($row) ;
              echo "<br>"; 
            }
            die;
        }
    
        $cnt = 0;
       
        foreach ($data as $row) {
            $itemcode = trim( $row["буква  колонуи с  артикулом"] ?? '');
            $item = new   \App\Entity\Item();
            
           
            $item->item_code=$itemcode;   
          //  $item->item_code= \App\Entity\Item::getNextArticle(); // автоматическое  создание артикула   

            if(strlen($itemcode) >0 )  { //ищем существующий
               $item= \App\Entity\Item::getFirst("item_code=".\App\Entity\Item::qstr($itemcode)) ;    
            }
            //другие  способы  сравнения как  пример  в  импорте service/import.php
            
            //раскоментировать нужные
            //yназвания  остальных  полей  одно  посмотреть по  коду  справочника  ТМЦ  reference/itemlist
        //     $item->itemname = trim($row["буква  колонки с  названием"] ?? '');
        //     $item->price1 = str_replace(',', '.', trim($row["буква  колонки с  ценой"] ?? ''));
        //     $item->shortname = trim($row["буква  колонки с короким названием  "] ?? '');
        //     $item->bar_code ...   штрих код
        //     $item->msr ...   единица измерения
        //     $item->cell ...   ячейка
        //     $item->manufacturer ...   бренд, производитель
        //     $item->minqty ...   мин. количество
        //     $item->cat_id ...   id категории
        //     $item->isweight ...   весовой товар  0 или  1
        
            
             //  $cf=[];  //кастоные поля если  есть
            //   $cf['код поля']= "значение  с  колонки" ;
            //   $item->savecf($cf)  ;
             
               
               $item->save()  ;
               $cnt++;
        }
        echo "<br> Импортировано {$cnt} ТМЦ";
        
} catch (Exception $e) {
    echo $e->getMessage();
    $logger->error($e);
}
 