<?php

namespace App\Pages;

//страница  для  загрузки  файла отчета
class ShowReport extends \Zippy\Html\WebPage
{

    public function __construct($type, $filename) {


        $html = \App\Session::getSession()->printform;

        if ($type == "preview") {
            header("Content-Type: text/html;charset=UTF-8");
        }
        if ($type == "print") {
            header("Content-Type: text/html;charset=UTF-8");
        }
        if ($type == "doc") {
            header("Content-type: application/vnd.ms-word");
            header("Content-Disposition: attachment;Filename={$filename}.doc");
            header("Content-Transfer-Encoding: binary");
        }
        if ($type == "xls") {
            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment;Filename={$filename}.xls");
            header("Content-Transfer-Encoding: binary");
            //echo '<meta http-equiv=Content-Type content="text/html; charset=windows-1251">';
        }
        if ($type == "html") {
            header("Content-type: text/plain");
            header("Content-Disposition: attachment;Filename={$filename}.html");
            header("Content-Transfer-Encoding: binary");
        }
        if ($type == "xml") {
            header("Content-type: text/xml");
            header("Content-Disposition: attachment;Filename={$filename}.xml");
            header("Content-Transfer-Encoding: binary");
            $html = \App\Session::getSession()->printxml;
        }
        if ($type == "pdf") {
            header("Content-type: application/pdf");
            header("Content-Disposition: attachment;Filename={$filename}.pdf");
            header("Content-Transfer-Encoding: binary");


            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);

            // (Optional) Setup the paper size and orientation
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->set_option('defaultFont', 'DejaVu Sans');
            // Render the HTML as PDF
            $dompdf->render();

            // Output the generated PDF to Browser
            $html = $dompdf->output();
        }

        echo $html;


        die;
    }

}
