<?php

namespace App\Modules\HR;

use App\System;
use App\Helper as H;

/**
 * Вспомагательный  класс
 */
class Helper
{
    public static function connect() {

        $modules = System::getOptions("modules");

        try {
            $ret =   self::make_request("GET", "/api/v1/order_status_options/list", null);
        } catch(\Exception $ee) {
            System::setErrorMsg($ee->getMessage());
            return;
        }

        if(!is_array($ret)) {
            //System::setSuccessMsg("Успішне з`єднання");
            return;
        }

        $list = array();
        foreach($ret['order_status_options'] as $st) {
            $list[$st['name']]=$st['title'] ;
        }

        return $list;

    }

    public static function make_request($method, $url, $body='') {

        $modules = System::getOptions("modules");
        $usessl = $modules['pussl'];


        $headers = array(
            'Authorization: Bearer ' . $modules['puapitoken'],
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://my.prom.ua'  . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (strtoupper($method) == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if (strlen($body)>0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $usessl == 1);
      //  \App\Helper::log(json_encode($body, JSON_UNESCAPED_UNICODE)) ;
        $result = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new  \Exception(curl_error($ch));
        }
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($httpcode >=300) {
            throw new  \Exception("http code ".$httpcode);
        }
        curl_close($ch);

        $ret = json_decode($result, true)  ;
        if (strlen($ret['error']) > 0) {
            throw new  \Exception($ret['error']);
        }
                
        return json_decode($result, true);
    }
    
    
 /*
   public static function productsToCsv($products)
    {

        // Имя файла CSV для сохранения данных
        $csvFileName = 'productsPrjaProm_' . date("d-m-Y") . '.csv';

        // Открываем файл CSV для записи (если файла нет, он будет создан)
        $file = fopen($csvFileName, 'w');

        // Записываем данные о продуктах
        foreach ($products as $product) {
            $row = [];
            $attrs = $product->getAttrList();

            $row['Код_товара'] = $product->item_code;
            $row['Название_позиции'] = $product->itemname;
            $row['Название_позиции_укр'] = $product->itemname;
            $row['Поисковые_запросы'] = $product->itemname;
            $row['Поисковые_запросы_укр'] = $product->itemname;
            $row['Описание'] = $product->dscription;
            $row['Описание_укр'] = $product->dscription;
 

     
            $row['Цена'] = round($product->price1);
            $row['Валюта'] = 'UAH';
            $row['Единица_измерения'] = $product->msr;
            $row['Оптовая_цена'] = round($product->price2);

            //картинки
            $imgs = $product->getImages();
            $row['images'] = implode(",", array_map(function ($numberImage) {
                return "https://vello-doro.eu/loadshopimage.php?id=" . $numberImage;
            }, $imgs));
            $row['images'] .= ",https://vello-doro.eu/loadshopimage.php?id=" . $product->image_id;

            $row['Ссылка_изображения'] .= explode(',', $row['images'])[0];

            $row['Наличие'] = '+';
            $row['Количество'] = '10000';
            $row['Номер_группы'] = '123627010';
            $row['Адрес_подраздела'] = 'https://prom.ua/Pryazha';
            $row['Возможность_поставки'] = '';
            $row['Срок_поставки'] = '';
            $row['Способ_упаковки'] = '';
            $row['Способ_упаковки_укр'] = '';
            $row['Уникальный_идентификатор'] = $product->item_code;
            $row['Идентификатор_товара'] = $product->item_code;
            $row['Идентификатор_подраздела'] = 408;
            $row['Идентификатор_группы'] = '';
            $row['Производитель'] = $product->manufacturer;
            $row['Страна_производитель'] = $product->country;
            $row['Скидка'] = '';
            $row['ID_группы_разновидностей'] = '';
            $row['Личные_заметки'] = '';
            $row['Продукт_на_сайте'] = '';
            $row['Срок_действия_скидки_от'] = '';
            $row['Срок_действия_скидки_до'] = '';
            $row['Цена_от'] = '';
            $row['Ярлык'] = '';
            $row['HTML_заголовок'] = '';
            $row['HTML_заголовок_укр'] = '';
            $row['HTML_описание'] = '';
            $row['HTML_описание_укр'] = '';
            $row['Код_маркировки_(GTIN)'] = '';
            $row['Номер_устройства_(MPN)'] = '';
            $row['Вес,кг'] = '';
            $row['Ширина,см'] = '';
            $row['Высота,см'] = '';
            $row['Длина,см'] = '';
            $row['Где_находится_товар'] = '';

            $row['Название_Характеристики'] = 'Стан';
            $row['Измерение_Характеристики'] = '';
            $row['Значение_Характеристики'] = 'Нове';

 
             $arrProducts[] = $row;
        }

        // Устанавливаем точку с запятой как разделитель
        $delimiter = ';';

        // Устанавливаем кодировку для текста
        $encoding = 'windows-1251';


        // Записываем заголовки столбцов (если необходимо)
        if (!empty($arrProducts)) {
            $columns = array_keys($arrProducts[0]);

            foreach ($attrs as $attr) {
                $columns [] = 'Название_Характеристики';
                $columns [] = 'Измерение_Характеристики';
                $columns [] = 'Значение_Характеристики';
            }

            $fields = array_map(function ($field) use ($encoding) {
                return iconv('UTF-8', $encoding, $field);
            }, $columns);

            fputcsv($file, $fields, ';');
        }

        foreach ($products as $product) {
            $row = [];
            $attrs = $product->getAttrList();
        }

        // Записываем данные о продуктах
        $i = 0;
        foreach ($arrProducts as $arrProduct) {

            $attrs = $products[$i]->getAttrList();
            $i++;

            $values = array_values($arrProduct);

            foreach ($attrs as $attr) {
                $values [] = $attr->attributename;
                $values [] = '';
                $values [] = $attr->value;
            }

            $fields = array_map(function ($field) use ($encoding) {
                return iconv('UTF-8', $encoding, $field);
            }, $values);

            fputcsv($file, $fields, ';');
        }

        // Закрываем файл
        fclose($file);

    }  
      */    
    

}
