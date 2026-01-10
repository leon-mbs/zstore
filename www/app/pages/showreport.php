<?php

namespace App\Pages;

//страница  для  загрузки  файла отчета
class ShowReport extends \Zippy\Html\WebPage
{
    public function __construct($type, $filename="") {
        parent::__construct();

        $common = \App\System::getOptions('common');
        $user = \App\System::getUser();
        if ($user->user_id == 0) {
            http_response_code(401);
            die;
        }

        $filename = $filename . date('_Y_m_d');
        $html = \App\Session::getSession()->printform;
        if (strlen($html) == 0) {
            http_response_code(404);
            die;
        }    
        
        
        \App\Session::getSession()->printform="";
  
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

        //    $file = tempnam(sys_get_temp_dir(), "".time());

        //    file_put_contents($file, $html);

            $reader =  new \PhpOffice\PhpSpreadsheet\Reader\Html()  ;
            $spreadsheet = $reader->loadFromString($html);


            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment;Filename={$filename}.xlsx");
            $writer->save('php://output');
            die;


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
            \App\Session::getSession()->printxml="";
        }
        if ($type == "pdf") {
            header("Content-type: application/pdf");
            header("Content-Disposition: attachment;Filename={$filename}.pdf");
            header("Content-Transfer-Encoding: binary");

            $dompdf = new \Dompdf\Dompdf(array('defaultFont' => 'DejaVu Sans'));

            //  $dompdf->set_option('defaultFont', 'DejaVu Sans');
            $dompdf->loadHtml($html);

            // (Optional) Setup the paper size and orientation
            $dompdf->setPaper('A4', 'landscape');
            // Render the HTML as PDF
            $dompdf->render();

            // Output the generated PDF to Browser
            $html = $dompdf->output();
        }
        header('Content-Length: '.strlen($html));
        echo $html;
        flush()  ;
        die;
    }

}
