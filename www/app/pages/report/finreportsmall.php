<?php

namespace App\Pages\Report;

use App\Application as App;
use App\Entity\Account;
use App\Entity\AccEntry;

use App\Helper as H;
use App\System as System;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * Баланс  малого предприятия
 */
class FinReportSmall extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('FinReportSmall')) {
            return;
        }

        $this->add(new Form('filter'));
        $this->filter->add(new DropDownChoice('yr'));
        $this->filter->add(new DropDownChoice('qw'));
        $this->filter->add(new SubmitButton('show'))->onClick($this, 'OnSubmit');

        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new ClickLink('exml',$this,'export'));
        $this->detail->add(new Label('preview'));
        \App\Session::getSession()->issubmit = false;
    }

 
 

    public function OnSubmit($sender) {


        $this->detail->setVisible(true);

        $html = $this->generateReport();
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $this->detail->preview->setText($html, true);

              

    }

    private function getData() {
  
 
 

        //$detail = array();
        $totstartdt = 0;
        $totstartct = 0;
        $totobdt = 0;
        $totobct = 0;
        $totenddt = 0;
        $totendct = 0;

        $year = $this->filter->yr->getValue();
        $qw = $this->filter->qw->getValue();

        $date = new \App\DateTime(strtotime(date($year.'-01-01 00:00:00')));
        $date->startOfYear();
        $from = $date->getTimestamp();
        $date->addMonth($qw * 3);
        $to = $date->getTimestamp() - 1;
   
        
        $firm = \App\System::getOptions("firm");
 
               
        
        //актив
        $b1011 = Account::getSaldo(10,$from)['dt'] + Account::getSaldo(11,$from)['dt'] + Account::getSaldo(12,$from)['dt'];
        $e1011 = Account::getSaldo(10,$to)['dt'] + Account::getSaldo(11,$to)['dt'] + Account::getSaldo(12,$to)['dt'];
        
        $b1012 = Account::getSaldo(13,$from)['dt'];
        $e1012 = Account::getSaldo(13,$to)['dt'];

        $b1010 = $b1011 - $b1012;
        $e1010 = $e1011 - $e1012;

        $b1005 = Account::getSaldo(15,$from)['dt'];
        $e1005 = Account::getSaldo(15,$to)['dt'];

        $b1095 = $b1005 + $b1010;
        $e1095 = $e1005 + $e1010;

        $b1100 = Account::getSaldo(20,$from)['dt'] + Account::getSaldo(22,$from)['dt'] + Account::getSaldo(23,$from)['dt'];
        $e1100 = Account::getSaldo(20,$to)['dt'] + Account::getSaldo(22,$to)['dt'] + Account::getSaldo(23,$to)['dt'];
        $b1103 = Account::getSaldo(26,$from)['dt'] + Account::getSaldo(28,$from)['dt'];
        $e1103 = Account::getSaldo(26,$to)['dt'] + Account::getSaldo(28,$to)['dt'];


        $b1100 = $b1100 + $b1103;
        $e1100 = $e1100 + $e1103;

        $b1125 =  Account::getSaldo(36,$from)['dt'];
        $e1125 =  Account::getSaldo(36,$to)['dt'];
        $b1135 = Account::getSaldo(241,$from)['dt'] + Account::getSaldo(242,$from)['dt'];
        $e1135 = Account::getSaldo(241,$to)['dt'] + Account::getSaldo(242,$to)['dt'];
        $b1136 = 0;//SubConto::getAmount($from,641,0,0,0,0,0,666);
        $e1136 = 0;//SubConto::getAmount($to,641,0,0,0,0,0,666);
        $b1155 = Account::getSaldo(63,$from)['dt'] + Account::getSaldo(37,$from)['dt'] + Account::getSaldo(68,$from)['dt'];
        $e1155 = Account::getSaldo(63,$to)['dt'] + Account::getSaldo(37,$to)['dt'] + Account::getSaldo(68,$to)['dt'];
        $b1165 = Account::getSaldo(30,$from)['dt'] + Account::getSaldo(31,$from)['dt'];
        $e1165 = Account::getSaldo(30,$to)['dt'] + Account::getSaldo(31,$to)['dt'];
        $b1190 = Account::getSaldo(643,$from)['dt'] + Account::getSaldo(644,$from)['dt'];
        $e1190 = Account::getSaldo(643,$to)['dt'] + Account::getSaldo(644,$to)['dt'];

        $b1195 = $b1100 + $b1125 + $b1135 + $b1155 + $b1165 + $b1190;
        $e1195 = $e1100 + $e1125 + $e1135 + $e1155 + $e1165 + $e1190;

        $b1300 = $b1095 + $b1195;
        $e1300 = $e1095 + $e1195;

        //пассив

        $b1400 = Account::getSaldo(40,$from)['ct'];
        $e1400 = Account::getSaldo(40,$to)['ct'];

        $b1420 = Account::getSaldo(79,$from)['ct'];
        $e1420 = Account::getSaldo(79,$to)['ct'];

        $b1495 = $b1420;
        $e1495 = $e1420;

        $b1420 = $b1420 > 0 ? $b1420 : "({$b1420})";
        $e1420 = $e1420 > 0 ? $e1420 : "({$e1420})";

        $b1615 = Account::getSaldo(63,$from)['ct'];
        $e1615 = Account::getSaldo(63,$to)['ct'];
        $b1620 = Account::getSaldo(641,$from)['ct'];
        $e1620 = Account::getSaldo(641,$to)['ct'];
        // $b1621 = SubConto::getAmount($from,641,0,0,0,0,0,666);
        // $e1621 = SubConto::getAmount($to,641,0,0,0,0,0,666);

        $b1630 = Account::getSaldo(66,$from)['ct'];
        $e1630 = Account::getSaldo(66,$to)['ct'];
        $b1690 = Account::getSaldo(36,$from)['ct'] + Account::getSaldo(37,$from)['ct'] + Account::getSaldo(643,$from)['ct'] + Account::getSaldo(644,$from)['ct'] + Account::getSaldo(68,$from)['ct'];
        $e1690 = Account::getSaldo(36,$to)['ct'] + Account::getSaldo(37,$to)['ct'] + Account::getSaldo(643,$to)['ct'] + Account::getSaldo(644,$to)['ct'] + Account::getSaldo(68,$to)['ct'];

        $b1695 = $b1615 + $b1620 + $b1630 + $b1690;
        $e1695 = $e1615 + $e1620 + $e1630 + $e1690;

        $b1900 = $b1400 + $b1495 + $b1695;
        $e1900 = $e1400 + $e1495 + $e1695;


        //форма 2
        $_from = strtotime('-1 year', $from);
        $_to = strtotime('-1 year', $to);


        $ob70 = Account::getOb(70,$from, $to);
        $b2000 = $ob70['ct']  ;
        $b2000 -= Account::getObBetweenAccount(70, 30, $from, $to);
        $b2000 -= Account::getObBetweenAccount(70, 31, $from, $to);
        $b2000 -= Account::getObBetweenAccount(70, 36, $from, $to);
        $b2000 -= Account::getObBetweenAccount(70, 641, $from, $to);
        $b2000 -= Account::getObBetweenAccount(70, 642, $from, $to);
        $b2000 -= Account::getObBetweenAccount(70, 643, $from, $to);

        $ob71 = Account::getOb(71,$from, $to);
        $b2120 = $ob71['ct'];
        $b2120 -= Account::getObBetweenAccount(71, 641, $from, $to);
        $b2120 -= Account::getObBetweenAccount(71, 643, $from, $to);

        //$ob72 = $a72->getSaldoAndOb($from,$to);
        //$b2240   = $ob72['obct'];  73 74
        $b2240 = 0;
        $b2280 = $b2000 + $b2120 + $b2240;

        $ob90 = Account::getOb(90,$from, $to);
        $ob91 = Account::getOb(91,$from, $to);
        $ob92 = Account::getOb(92,$from, $to);
        $ob93 = Account::getOb(93,$from, $to);
        $ob94 = Account::getOb(94,$from, $to);
        $ob97 = Account::getOb(97,$from, $to);
        // $ob98 = $a98->getSaldoAndOb($from,$to);
        $b2050 = $ob90['dt'];
        $b2180 = $ob92['dt'] + $ob93['dt'] + $ob94['dt'];

        $b2270 = $ob97['dt'];
        $b2285 = $b2050 + $b2180 + $b2270;
        $b2290 = $b2280 - $b2285;
        
        $b2350 = $b2290    ;

        $ob70 = Account::getOb(70,$_from, $_to);
        $e2000 = $ob70['ct']  ;
        $e2000 -= Account::getObBetweenAccount(70, 30, $_from, $_to);
        $e2000 -= Account::getObBetweenAccount(70, 31, $_from, $_to);
        $e2000 -= Account::getObBetweenAccount(70, 36, $_from, $_to);
        $e2000 -= Account::getObBetweenAccount(70, 641, $_from, $_to);
        $e2000 -= Account::getObBetweenAccount(70, 642, $_from, $_to);
        $e2000 -= Account::getObBetweenAccount(70, 643, $_from, $_to);

        $ob71 = Account::getOb(71,$_from, $_to);
        $e2120 = $ob71['ct'];
        $e2120 -= Account::getObBetweenAccount(71, 641, $_from, $_to);
        $e2120 -= Account::getObBetweenAccount(71, 643, $_from, $_to);

        //$ob72 = $a72->getSaldoAndOb($_from,$_to);
        //$e2240   = $ob72['obct'];
        $e2240 = 0;
        $e2280 = $e2000 + $e2120 + $e2240;

        $ob90 = Account::getOb(90,$_from, $_to);
        $ob91 = Account::getOb(91,$_from, $_to);
        $ob92 = Account::getOb(92,$_from, $_to);
        $ob93 = Account::getOb(93,$_from, $_to);
        $ob94 = Account::getOb(94,$_from, $_to);
        $ob97 = Account::getOb(97,$_from, $_to);
        // $ob98 = $a98->getSaldoAndOb($_from,$_to);
        $e2050 = $ob90['dt'];
        $e2180 = $ob92['dt'] + $ob93['dt'] + $ob94['dt'];

        $e2270 = $ob97['dt'];
        $e2285 = $e2050 + $e2180 + $b2270;
        $e2290 = $e2280 - $e2285;
        //$e2300 =  $ob98['obdt']   ;
        $e2350 = $e2290  ;


        $header = array(
            'date1y' => date('Y', time()),
            'date1m' => date('m', time()),
            'date1d' => date('d', time()),
            'date2' => date('d.m.Y', $to + 1),
            'edrpou' => (string) sprintf("%10d", $firm['tin']),
            'koatuu' => (string) sprintf("%10d", $firm['koatuu']),
            'kopfg' => (string) sprintf("%10d", $firm['kopfg']),
            'kodu' => (string) sprintf("%10d", $firm['kodu']),
            'kved' => (string) sprintf("%10s", $firm['kved']),
            'address' => $firm['address']??'' . ' ' . $firm['city']??'' . ', ' . $firm['phone']??'',
            'firmname' => $firm['firm_name'],
            'b1005' => H::fa($b1005),
            'e1005' => H::fa($e1005),
            'b1010' => H::fa($b1010),
            'e1010' => H::fa($e1010),
            'b1011' => H::fa($b1011),
            'e1011' => H::fa($e1011),
            'b1012' => H::fa($b1012),
            'e1012' => H::fa($e1012),
            'b1095' => H::fa($b1095),
            'e1095' => H::fa($e1095),
            'b1100' => H::fa($b1100),
            'e1100' => H::fa($e1100),
            'b1103' => H::fa($b1103),
            'e1103' => H::fa($e1103),
            'b1125' => H::fa($b1125),
            'e1125' => H::fa($e1125),
            'b1135' => H::fa($b1135),
            'e1135' => H::fa($e1135),
            'b1136' => H::fa($b1136),
            'e1136' => H::fa($e1136),
            'b1155' => H::fa($b1155),
            'e1155' => H::fa($e1155),
            'b1165' => H::fa($b1165),
            'e1165' => H::fa($e1165),
            'b1190' => H::fa($b1190),
            'e1190' => H::fa($e1190),
            'b1195' => H::fa($b1195),
            'e1195' => H::fa($e1195),
            'b1300' => H::fa($b1300),
            'e1300' => H::fa($e1300),
            'b1400' => H::fa($b1400),
            'e1400' => H::fa($e1400),
            'b1420' => H::fa($b1420),
            'e1420' => H::fa($e1420),
            'b1495' => H::fa($b1495),
            'e1495' => H::fa($e1495),
            'b1615' => H::fa($b1615),
            'e1615' => H::fa($e1615),
            'b1620' => H::fa($b1620),
            'e1620' => H::fa($e1620),
          //  'b1621' => H::fa($b1621),
          //  'e1621' => H::fa($e1621),
            'b1630' => H::fa($b1630),
            'e1630' => H::fa($e1630),
            'b1690' => H::fa($b1690),
            'e1690' => H::fa($e1690),
            'b1695' => H::fa($b1695),
            'e1695' => H::fa($e1695),
            'b1900' => H::fa($b1900),
            'e1900' => H::fa($e1900),
            'b2000' => H::fa($b2000),
            'e2000' => H::fa($e2000),
            'b2120' => H::fa($b2120),
            'e2120' => H::fa($e2120),
            'b2240' => H::fa($b2240),
            'e2240' => H::fa($e2240),
            'b2280' => H::fa($b2280),
            'e2280' => H::fa($e2280),
            'b2050' => H::fa($b2050),
            'e2050' => H::fa($e2050),
            'b2180' => H::fa($b2180),
            'e2180' => H::fa($e2180),
            'b2270' => H::fa($b2270),
            'e2270' => H::fa($e2270),
            'b2285' => H::fa($b2285),
            'e2285' => H::fa($e2285),
            'b2290' => H::fa($b2290),
            'e2290' => H::fa($e2290),
          //  'b2300' => H::fa($b2300),
          //  'e2300' => H::fa($e2300),
            'b2350' => H::fa($b2350),
            'e2350' => H::fa($e2350)
        );
      
        return $header;
    }

    public function generateReport() {
        
        $header = $this->getData()  ;
        
        $report = new \App\Report('report/finreportsmall.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function export($sender) {
        
        $header = $this->getData()  ;
        
        $xml=   $this->getXML($header)  ;

        $firm = \App\System::getOptions("firm");
       
        $edrpou = (string) sprintf("%10d", $firm['edrpou']);
        //2301 0011111111 J0901106 1 00 0000045 1 03 2015 2301.xml
        //1 - місяць, 2 - квартал, 3 - півріччя, 4 - 9 місяців, 5 - рік

        $number = (string) sprintf('%07d', 1);
        $filename = $firm['gni'] . $edrpou . "J0901106" . "100{$number}2" . $pm . $year . $firm['gni'] . ".xml";

        $filename = str_replace(' ', '0', $filename);
        
        
        header("Content-type: text/xml");
            header("Content-Disposition: attachment;Filename={$filename}.xml");
            header("Content-Transfer-Encoding: binary");        
            
        echo $xml;    
    }

 
     private function getXML($header) {
        $year = $this->filter->yr->getValue();
        $pm = (string) sprintf('%02d', 3 * $this->filter->qw->getValue());
        $common = System::getOptions("common");
        $firm = System::getOptions("firmdetail");
        $jf = ($common['juridical'] == true ? "J" : "F") . "0901106";

        $edrpou = (string) sprintf("%10d", $firm['edrpou']);
        //2301 0011111111 J0901106 1 00 0000045 1 03 2015 2301.xml
        //1 - місяць, 2 - квартал, 3 - півріччя, 4 - 9 місяців, 5 - рік

        $number = (string) sprintf('%07d', 1);
        $filename = $firm['gni'] . $edrpou . "J0901106" . "100{$number}2" . $pm . $year . $firm['gni'] . ".xml";

        $filename = str_replace(' ', '0', $filename);

        $xml = "<?xml version=\"1.0\" encoding=\"windows-1251\" ?>
  <DECLAR xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"J0901106.xsd\">
  <DECLARHEAD>
  <TIN>{$firm['edrpou']}</TIN>
  <C_DOC>J09</C_DOC>
  <C_DOC_SUB>011</C_DOC_SUB>
  <C_DOC_VER>6</C_DOC_VER>
  <C_DOC_TYPE>0</C_DOC_TYPE>
  <C_DOC_CNT>1</C_DOC_CNT>
  <C_REG>" . substr($firm['gni'], 0, 2) . "</C_REG>
  <C_RAJ>" . substr($firm['gni'], 2, 2) . "</C_RAJ>
  <PERIOD_MONTH>{$pm}</PERIOD_MONTH>
  <PERIOD_TYPE>2</PERIOD_TYPE>
  <PERIOD_YEAR>{$year}</PERIOD_YEAR>
  <C_STI_ORIG>{$firm['gni']}</C_STI_ORIG>
  <C_DOC_STAN>1</C_DOC_STAN>
  <LINKED_DOCS xsi:nil=\"true\" />
  <D_FILL>" . (string) date('dmY') . "</D_FILL>
  <SOFTWARE>Zippy ERP</SOFTWARE>
  </DECLARHEAD>
  <DECLARBODY>
  <HFILL>" . (string) date('dmY') . "</HFILL>
  <HNAME>{$firm['name']}<</HNAME>
  <HTIN>{$firm['edrpou']}</HTIN>
  <HKOATUU_S xsi:nil=\"true\" />
  <HKOATUU  >{$firm['koatuu']}</HKOATUU  >
  <HKOPFG_S xsi:nil=\"true\" />
  <HKOPFG  >{$firm['kopfg']}</HKOPFG  >
  <HKVED_S xsi:nil=\"true\" />
  <HKVED  >{$firm['kved']}</HKVED  >
  <HKIL xsi:nil=\"true\" />
  <HLOC xsi:nil=\"true\" />
  <HTEL xsi:nil=\"true\" />
  <HPERIOD1 />
  <HZY>{$year}</HZY>
  <R1005G3>{$header['b1005']}</R1005G3>
  <R1005G4>{$header['e1005']}</R1005G4>
  <R1010G3>{$header['b1010']}</R1010G3>
  <R1010G4>{$header['e1010']}</R1010G4>
  <R1011G3>{$header['b1011']}</R1011G3>
  <R1011G4>{$header['e1011']}</R1011G4>
  <R1012G3>{$header['b1012']}</R1012G3>
  <R1012G4>{$header['e1012']}</R1012G4>
  <R1095G3>{$header['b1095']}</R1095G3>
  <R1095G4>{$header['e1095']}</R1095G4>
  <R1100G3>{$header['b1100']}</R1100G3>
  <R1100G4>{$header['e1100']}</R1100G4>
  <R1103G3>{$header['b1103']}</R1103G3>
  <R1103G4>{$header['e1103']}</R1103G4>
  <R1125G3>{$header['b1125']}</R1125G3>
  <R1125G4>{$header['e1125']}</R1125G4>
  <R1135G3>{$header['b1135']}</R1135G3>
  <R1135G4>{$header['e1135']}</R1135G4>
  <R1136G3>{$header['b1126']}</R1136G3>
  <R1136G4>{$header['e1126']}</R1136G4>
  <R1155G3>{$header['b1155']}</R1155G3>
  <R1155G4>{$header['e1155']}</R1155G4>
  <R1165G3>{$header['b1165']}</R1165G3>
  <R1165G4>{$header['e1165']}</R1165G4>
  <R1190G3>{$header['b1190']}</R1190G3>
  <R1190G4>{$header['e1190']}</R1190G4>
  <R1195G3>{$header['b1195']}</R1195G3>
  <R1195G4>{$header['e1195']}</R1195G4>
  <R1300G3>{$header['b1300']}</R1300G3>
  <R1300G4>{$header['e1300']}</R1300G4>
  <R1400G3>{$header['b1400']}</R1400G3>
  <R1400G4>{$header['e1400']}</R1400G4>
  <R1420G3>{$header['b1420']}</R1420G3>
  <R1420G4>{$header['e1420']}</R1420G4>
  <R1495G3>{$header['b1495']}</R1495G3>
  <R1495G4>{$header['e1495']}</R1495G4>
  <R1615G3>{$header['b1615']}</R1615G3>
  <R1615G4>{$header['e1615']}</R1615G4>
  <R1620G3>{$header['b1620']}</R1620G3>
  <R1620G4>{$header['e1620']}</R1620G4>
  <R1621G3>{$header['b1621']}</R1621G3>
  <R1621G4>{$header['e1621']}</R1621G4>
  <R1630G3>{$header['b1630']}</R1630G3>
  <R1630G4>{$header['e1630']}</R1630G4>
  <R1690G3>{$header['b1690']}</R1690G3>
  <R1690G4>{$header['e1690']}</R1690G4>
  <R1695G3>{$header['b1695']}</R1695G3>
  <R1695G4>{$header['e1695']}</R1695G4>
  <R1900G3>{$header['b1900']}</R1900G3>
  <R1900G4>{$header['e1900']}</R1900G4>
  <R2000G3>{$header['b2000']}</R2000G3>
  <R2000G4>{$header['e2000']}</R2000G4>
  <R2120G3>{$header['b2120']}</R2120G3>
  <R2120G4>{$header['e2120']}</R2120G4>
  <R2240G3>{$header['b2240']}</R2240G3>
  <R2240G4>{$header['e2240']}</R2240G4>
  <R2280G3>{$header['b2280']}</R2280G3>
  <R2280G4>{$header['e2280']}</R2280G4>
  <R2050G3>{$header['b2050']}</R2050G3>
  <R2050G4>{$header['e2050']}</R2050G4>
  <R2180G3>{$header['b2180']}</R2180G3>
  <R2180G4>{$header['e2180']}</R2180G4>
  <R2270G3>{$header['b2270']}</R2270G3>
  <R2270G4>{$header['e2270']}</R2270G4>
  <R2285G3>{$header['b2285']}</R2285G3>
  <R2285G4>{$header['e2285']}</R2285G4>
  <R2290G3>{$header['b2290']}</R2290G3>
  <R2290G4>{$header['e2290']}</R2290G4>
  <R2300G3>{$header['b2300']}</R2300G3>
  <R2300G4>{$header['e2300']}</R2300G4>
  <R2350G3>{$header['b2350']}</R2350G3>
  <R2350G4>{$header['e2350']}</R2350G4>







  <HBOS />
  <HBUH xsi:nil=\"true\" />
  </DECLARBODY>
  </DECLAR>";

        return $xml;
    }


}
