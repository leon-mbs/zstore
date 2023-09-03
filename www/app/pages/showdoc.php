<?php

namespace App\Pages;

use App\Entity\Doc\Document;

//страница  для  загрузки  файла экcпорта
class ShowDoc extends \Zippy\Html\WebPage
{
    public function __construct($type, $docid) {
        parent::__construct();

        $docid = intval($docid);


        $common = \App\System::getOptions('common');

        $user = \App\System::getUser();
        if ($user->user_id == 0) {
            die;
        }

        $doc = Document::load($docid);
        if ($doc == null) {
            echo "Не задан  документ";
            die;
        }

        $doc = $doc->cast();
        $filename = $doc->document_number;

        $html = $doc->generateReport();

        if (strlen($html) > 0) {


            if ($type == "preview") {
                header("Content-Type: text/html;charset=UTF-8");
                echo $html;
            }
            if ($type == "print") {
                header("Content-Type: text/html;charset=UTF-8");
                echo $html;
            }
            if ($type == "pos") {
                header("Content-Type: text/html;charset=UTF-8");
                $html = $doc->generatePosReport();
                echo $html;
            }
            if ($type == "doc") {
                header("Content-type: application/vnd.ms-word");
                header("Content-Disposition: attachment;Filename={$filename}.doc");
                header("Content-Transfer-Encoding: binary");

                echo $html;
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

                echo $html;
            }
            if ($type == "xml") {
                $xml = $doc->exportGNAU();
                header("Content-type: text/xml");
                header("Content-Disposition: attachment;Filename={$xml['filename']}");
                header("Content-Transfer-Encoding: binary");

                echo $xml['content'];
            }
            if ($type == "pdf") {
                header("Content-type: application/pdf");
                header("Content-Disposition: attachment;Filename={$filename}.pdf");
                header("Content-Transfer-Encoding: binary");

                $dompdf = new \Dompdf\Dompdf(array('isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans'));
                $dompdf->loadHtml($html);

                // (Optional) Setup the paper size and orientation
                $dompdf->setPaper('A4', 'landscape');
                //                $dompdf->set_option('defaultFont', 'DejaVu Sans');
                // Render the HTML as PDF
                $dompdf->render();

                // Output the generated PDF to Browser
                $html = $dompdf->output();
                echo $html;
            }
        } else {
            //$html = "<h4>Печатная форма  не  задана</h4>";
        }

        if ($type == "metaie") { //to do экспорт  файлов  метаобьекта
            $filename = $doc->meta_name . ".zip";

            header("Content-type: application/zip");
            header("Content-Disposition: attachment;Filename={$filename}");
            header("Content-Transfer-Encoding: binary");

            // echo $zip;
        }
        die;
    }

}
