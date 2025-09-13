<?php

namespace App;

use \App\Util;

/**
* класс для  формирования  ESC/POS команд
*/
class Printer
{
    /**
     * ASCII null control character
     */
    public const NUL = 0x00;

    /**
     * ASCII linefeed control character
     */
    public const LF = 0x0a;

    /**
     * ASCII escape control character
     */
    public const ESC = 0x1b;

    /**
     * ASCII form separator control character
     */
    public const FS = 0x1c;

    /**
     * ASCII form feed control character
     */
    public const FF = 0x0c;

    /**
     * ASCII group separator control character
     */
    public const GS = 0x1d;

    /**
     * ASCII data link escape control character
     */
    public const DLE = 0x10;

    /**
     * ASCII end of transmission control character
     */
    public const EOT = 0x04;



    /**
     * Indicates JAN13 barcode when used with Printer::barcode
     */
    public const BARCODE_EAN13 = 67;



    /**
     * Indicates CODE39 barcode when used with Printer::barcode
     */
    public const BARCODE_CODE39 = 69;



    /**
     * Indicates CODE128 barcode when used with Printer::barcode
     */
    public const BARCODE_CODE128 = 73;

    /**
     * Indicates that HRI (human-readable interpretation) text should not be
     * printed, when used with Printer::setBarcodeTextPosition
     */
    public const BARCODE_TEXT_NONE = 0;

    /**
     * Indicates that HRI (human-readable interpretation) text should be printed
     * above a barcode, when used with Printer::setBarcodeTextPosition
     */
    public const BARCODE_TEXT_ABOVE = 1;

    /**
     * Indicates that HRI (human-readable interpretation) text should be printed
     * below a barcode, when used with Printer::setBarcodeTextPosition
     */
    public const BARCODE_TEXT_BELOW = 2;

    /**
     * Use the first color (usually black), when used with Printer::setColor
     */
    public const COLOR_1 = 0;

    /**
     * Use the second color (usually red or blue), when used with Printer::setColor
     */
    public const COLOR_2 = 1;

    /**
     * Make a full cut, when used with Printer::cut
     */
    public const CUT_FULL = 65;

    /**
     * Make a partial cut, when used with Printer::cut
     */
    public const CUT_PARTIAL = 66;

    /**
     * Use Font A, when used with Printer::setFont
     */
    public const FONT_A = 0;

    /**
     * Use Font B, when used with Printer::setFont
     */
    public const FONT_B = 1;

    /**
     * Use Font C, when used with Printer::setFont
     */
    public const FONT_C = 2;

    /**
     * Use default (high density) image size, when used with Printer::graphics,
     * Printer::bitImage or Printer::bitImageColumnFormat
     */
    public const IMG_DEFAULT = 0;

    /**
     * Use lower horizontal density for image printing, when used with Printer::graphics,
     * Printer::bitImage or Printer::bitImageColumnFormat
     */
    public const IMG_DOUBLE_WIDTH = 1;

    /**
     * Use lower vertical density for image printing, when used with Printer::graphics,
     * Printer::bitImage or Printer::bitImageColumnFormat
     */
    public const IMG_DOUBLE_HEIGHT = 2;

    /**
     * Align text to the left, when used with Printer::setJustification
     */
    public const JUSTIFY_LEFT = 0;

    /**
     * Center text, when used with Printer::setJustification
     */
    public const JUSTIFY_CENTER = 1;

    /**
     * Align text to the right, when used with Printer::setJustification
     */
    public const JUSTIFY_RIGHT = 2;

    /**
     * Use Font A, when used with Printer::selectPrintMode
     */
    public const MODE_FONT_A = 0;

    /**
     * Use Font B, when used with Printer::selectPrintMode
     */
    public const MODE_FONT_B = 1;

    /**
     * Use text emphasis, when used with Printer::selectPrintMode
     */
    public const MODE_EMPHASIZED = 8;

    /**
     * Use double height text, when used with Printer::selectPrintMode
     */
    public const MODE_DOUBLE_HEIGHT = 16;

    /**
     * Use double width text, when used with Printer::selectPrintMode
     */
    public const MODE_DOUBLE_WIDTH = 32;

    /**
     * Underline text, when used with Printer::selectPrintMode
     */
    public const MODE_UNDERLINE = 128;

    /**
     * Indicates standard PDF417 code
     */
    public const PDF417_STANDARD = 0;

    /**
     * Indicates truncated PDF417 code
     */
    public const PDF417_TRUNCATED = 1;

    /**
     * Indicates error correction level L when used with Printer::qrCode
     */
    public const QR_ECLEVEL_L = 0;

    /**
     * Indicates error correction level M when used with Printer::qrCode
     */
    public const QR_ECLEVEL_M = 1;

    /**
     * Indicates error correction level Q when used with Printer::qrCode
     */
    public const QR_ECLEVEL_Q = 2;

    /**
     * Indicates error correction level H when used with Printer::qrCode
     */
    public const QR_ECLEVEL_H = 3;

    /**
     * Indicates QR model 1 when used with Printer::qrCode
     */
    public const QR_MODEL_1 = 1;

    /**
     * Indicates QR model 2 when used with Printer::qrCode
     */
    public const QR_MODEL_2 = 2;

    /**
     * Indicates micro QR code when used with Printer::qrCode
     */
    public const QR_MICRO = 3;



    /**
     * Indicates no underline when used with Printer::setUnderline
     */
    public const UNDERLINE_NONE = 0;



    private $buffer=[];
    private $wc=32;
    private $cp=866;
  
    public function __construct($labelmode=false) {

        // $options = \App\System::getOptions('printer')  ;
        $user = \App\System::getUser() ;

        $this->wc = intval($user->pwsym) ;
        if($this->wc==0) {
            $this->wc=32;
        }
        $this->cp = intval($user->pcp) ;
        if($this->cp==0) {
            $this->cp=866;
        }
        if($labelmode) {
            $this->wc = intval($user->pwsymlabel) ;
            if($this->wc==0) {
                $this->wc=32;
            }
            $this->cp = intval($user->pcplabel) ;
            if($this->cp==0) {
                $this->cp=866;
            }
        }

 
        $this->buffer[] = self::ESC;
        $this->buffer[] = ord('@');

        $this->addBytes([27,116,17]); //866
    }

    private function encode($text) {
        // $text = \Normalizer::normalize($text);
        if ($text === false) {
        //  throw new \Exception("Input must be UTF-8");
        }
        //украинское i на  ангглийсккое  хз  почему
        $text = str_replace("і", "i", $text);
        $text = str_replace("І", "I", $text);
   //     $text = mb_convert_encoding($text, "cp866", "utf-8");
   
        if($this->cp==1251) {
            $text = iconv('UTF-8','windows-1251',$text)  ;
        } else {
            $text = iconv('UTF-8','cp866',$text)  ;
        }

        return $text;
    }
    public function getBuffer() {
        return $this->buffer;
    }
    public function addBytes(array $bytes) {
        foreach($bytes as $b) {
            $this->buffer[]= $b;
        }

    }
   public function addString(string $str) {
        $bytes = str_split($str) ;
       
        foreach($bytes as $b) {
            $this->buffer[]= ord($b); 
        }

    }


    public function align($align) {
        $this->buffer[]= 0x1b;
        $this->buffer[]= 0x61;
        $this->buffer[]= $align;
  

    }
    public function lm($margin) {
        $this->buffer[]= self::GS;
        $this->buffer[]= ord('L');
        $this->buffer[]= $this->intLowHigh($margin, 2);
 
    }
    public function newline() {
        $this->buffer[]= self::LF;

    }
    public function beep() {
        $this->buffer[]= self::ESC;
        $this->buffer[]= ord('B');
        $this->buffer[]= 2;
        $this->buffer[]= 1;

    }
    public function cut($partial = false) {
        if($partial) {
            $this->buffer[]= self::CUT_PARTIAL;
        } else {
            $this->buffer[]= self::CUT_FULL;
        }

    }

    /**
    * открыть ящик .  Пин  2 или  5
    *
    * @param mixed $pin
    */
    public function cash($pin) {
        if($pin==2 || $pin==5) {
            $this->buffer[]=self::ESC;
            $this->buffer[]= 0x70;

            $this->buffer[]= $pin == 2 ? 0 : 1;
        }

    }

    /**
    * вывод текста
    *
    * @param mixed $text
    * @param mixed $newline   перевод на  новую  строку
    */
    public function text($text, $newline=true) {
        if(strlen($text)==0) {
            return;
        }
        
        $a=[];
        if(mb_strlen($text) >$this->wc )  {
           $a =  Util::splitstr($text, $this->wc) ;
        } else {
           $a[]=$text;  
        }
        
        $i=0;
        foreach($a as $ap) {
            $ap = $this->encode($ap)  ;
            $t = str_split($ap) ;

            foreach($t as $b) {
                $this->buffer[]= ord($b);
            }
            $i++;

            if($i<count($a)) {
                $this->newline()  ;
            }

        }


        if($newline) {
            $this->newline()  ;
        }
    }

    public function labelrow($text ) {
        if(strlen($text)==0) {
            return;
        }

        $text = str_replace("'","`",$text) ;
    //    $text = str_replace("\"","`",$text) ;
        
        
        if($this->cp==866) {
            $text = iconv('UTF-8','cp866',$text)  ;
        } else {
            $text = iconv('UTF-8','windows-1251',$text)  ;
        }  
        
            
        $t = str_split($text) ;

        foreach($t as $b) {
            $this->buffer[]= ord($b);
        }
        $this->newline()  ;

    }
 
    /**
    * разделительЮ строка  симолов по  всей  ширине
    *
    * @param mixed $symbol
    * @return mixed
    */
    public function separator($symbol) {
        if(strlen($symbol) !=1) {
            return;
        }

        $text=str_repeat($symbol, $this->wc) ;

        $this->text($text) ;
    }

    /**
    * вывод QR кода
    *
    * @param mixed $text
    * @param int $size     
    */
    public function QR($text, int $size=12) {
      
        if($size <1 || $size >24) {
            $size=12;
        }
        
//  $text="https://cabinet.tax.gov.ua/cashregs/check?id=TEST-_SNWpN&date=20250224&time=19%3A20%3A40&fn=TEST398593&sm=210.00&mac=8d1a9061525192ba4ece6238f06596c552cbe318954df87809ef39c796bda7b5";     

   
  if(false){  
  
  
  $this->buffer=[];   
  $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector( );
$printer = new \Mike42\Escpos\Printer($connector);
 
        
$printer->qrCode($text,0,$size);        
        
  $ff=   $connector->getData();   
   $t = str_split($ff) ;
        foreach($t as $b) {
              $this->buffer[]= ord($b);
        }
$printer->close();
$b = json_encode( $this->buffer) ;

            \App\Helper::log($b) ;

        
    return; 

    }
    
        
  
        $ec = Printer::QR_ECLEVEL_L   ;
        $model = Printer::QR_MODEL_2   ;
        $cn = '1'; // Code type for QR code
        // Select model: 1, 2 or micro.
        $this -> wrapperSend2dCodeData(chr(65), $cn, chr(48 + $model) . chr(0));
        // Set dot size.
        $this -> wrapperSend2dCodeData(chr(67), $cn, chr($size));
        // Set error correction level: L, M, Q, or H
        $this -> wrapperSend2dCodeData(chr(69), $cn, chr(48 + $ec));
        // Send content & print
        $this -> wrapperSend2dCodeData(chr(80), $cn, $text, '0');
        $this -> wrapperSend2dCodeData(chr(81), $cn, '', '0');
  
     //   $b = json_encode( $this->buffer) ;

     //   \App\Helper::log($b) ;

    }
    
    
    /**
     * Wrapper for GS ( k, to calculate and send correct data length.
     *
     * @param string $fn Function to use
     * @param string $cn Output code type. Affects available data
     * @param string $data Data to send.
     * @param string $m Modifier/variant for function. Often '0' where used.
     * @throws InvalidArgumentException Where the input lengths are bad.
     */
    protected function wrapperSend2dCodeData($fn, $cn, $data = '', $m = '')
    {
          $this->buffer[]= self::GS;
        $header = $this -> intLowHigh(strlen($data) + strlen($m) + 2, 2);
        $str=  "(k" . $header . $cn . $fn . $m . $data ;
        
        $t = str_split($str) ;
        foreach($t as $b) {
             $this->buffer[]= ord($b);
        }        
        
    }    
 /**
     * Generate two characters for a number: In lower and higher parts, or more parts as needed.
     *
     * @param int $input Input number
     * @param int $length The number of bytes to output (1 - 4).
     */
    protected static function intLowHigh($input, $length)
    {
        $maxInput = (256 << ($length * 8) - 1);
        $outp = "";
        for ($i = 0; $i < $length; $i++) {
            $outp .= chr($input % 256);
            $input = (int)($input / 256);
        }
        return $outp;
    }
      
    
 
    public function QR_($text, int $size=12) {

        if($size <1 || $size >24) {
            $size=12;
        }

  
 
        //    $text = $this->encode($text)  ;
        $store_len = strlen($text) + 3;
        $store_pL = intval($store_len % 256);
        $store_pH = intval($store_len / 256);

        $model=[0x1d, 0x28, 0x6b, 0x04, 0x00, 0x31, 0x41,0x32,0 ] ;
        $size =[0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x43,intval($size) ] ;
        //         $size =[0x1b, 0x23, 0x23, 0x51, 0x50, 0x49, 0x58, 2 ] ;
        $error=[0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x45,0x31] ;
        $store=[0x1d, 0x28, 0x6b, $store_pL, $store_pH, 0x31, 0x50,0x30] ;
        $print=[0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x51,0x30] ;

        $this->addBytes($model) ;
        $this->addBytes($size) ;
        $this->addBytes($error) ;
        $this->addBytes($store) ;

        $t = str_split($text) ;
        foreach($t as $b) {
            $this->buffer[]= ord($b);
        }


        $this->addBytes($print) ;



    }
   

    /**
    * вывод QR кода
    *
    * @param mixed $text
    * @param mixed $type     EAN13 Code128 Code39
    */
    public function BarCode($text,   $type) {
        if($type == self::BARCODE_CODE128) {

            $text = "{B".$text;

            if (preg_match("/^\{[A-C][\\x00-\\x7F]+$/", $text) === 0) {
                throw new \Exception("invalid barcode {$text}");
            }

            $this->addBytes([self::GS , ord('k') , $type , (int)strlen($text) ]);

            $t = str_split($text) ;
            foreach($t as $b) {
                $this->buffer[]= ord($b);
            }


        }

        if($type == self::BARCODE_CODE39) {

            $text =strtoupper($text);

            if (preg_match("/^([0-9A-Z \$\%\+\-\.\/]+|\*[0-9A-Z \$\%\+\-\.\/]+\*)$/", $text) === 0) {
                throw new \Exception("invalid barcode {$text}");
            }

            $this->addBytes([self::GS , ord('k') , $type , (int)strlen($text) ]);

            $t = str_split($text) ;
            foreach($t as $b) {
                $this->buffer[]= ord($b);
            }


        }

        if($type == self::BARCODE_EAN13) {


            if (preg_match("/^[0-9]{12,13}$/", $text) === 0) {
                throw new \Exception("invalid barcode {$text}");
            }

            $this->addBytes([self::GS , ord('k') , $type , (int)strlen($text) ]);

            $t = str_split($text) ;
            foreach($t as $b) {
                $this->buffer[]= ord($b);
            }


        }



    }

    /**
    * высота  штрихкода 1 - 255
    *
    * @param mixed $height
    */
    public function barcodeHeight(  $height = 100) {
        if($height < 1 || $height > 255) {
            return;
        }
        $this->addBytes([self::GS , ord('h') ,   $height ]);

    }

    /**
    * ширина  штрихкода   2 - 6
    *
    * @param mixed $width
    */
    public function barcodeWidth(  $width = 2) {
        if($width < 2 || $width > 6) {
            return;
        }
        $this->addBytes([self::GS , ord('w') ,   $width ]);
    }

    /**
    * цвет
    *
    * @param int $color   0,1
    */
    public function color(int $color) {
        if($color == 0  || $color ==1) {
            $this->addBytes([self::ESC , ord('r') , $color])  ;
        }


    }
    /**
    * режим печати
    *
    * @param mixed $mode  MODE_FONT_A MODE_FONT_B  self::MODE_EMPHASIZED   self::MODE_DOUBLE_HEIGHT self::MODE_DOUBLE_WIDTH  self::MODE_UNDERLINE
    */
    public function mode($mode=self::MODE_FONT_A) {

        $allModes = Printer::MODE_FONT_B | self::MODE_EMPHASIZED | self::MODE_DOUBLE_HEIGHT | self::MODE_DOUBLE_WIDTH | self::MODE_UNDERLINE;
        if (!is_integer($mode) || $mode < 0 || ($mode & $allModes) != $mode) {
            throw new \Exception("Invalid mode");
        }


        $this->addBytes([self::ESC , ord('!') , $mode])  ;

    }

    /**
    * размер  текста
    *
    * @param int $widthMultiplier   1 - 8
    * @param int $heightMultiplier  1 - 8
    */
    public function textSize(int  $widthMultiplier, int $heightMultiplier) {
        if($widthMultiplier < 1 ||  $widthMultiplier >8) {
            $widthMultiplier=1;
        }
        if($heightMultiplier < 1 ||  $heightMultiplier >8) {
            $heightMultiplier=1;
        }
        $c = (2 << 3) * ($widthMultiplier - 1) + ($heightMultiplier - 1);
        $this->addBytes([self::GS , ord('!') , $c])  ;

    }

    /**
    * генерит  последовательность  команд по  xml
    *
    */
    public static function xml2comm($xml) {

        $pr = new \App\Printer() ;

        $allowed = ['size','separator','align','leftmargin','barcode','qrcode','text','font','newline','cash','cut','partialcut','beep','color','commands','row'];

        $xml = "<root>{$xml}</root>" ;
        $xml = @simplexml_load_string($xml) ;
        if($xml==false) {
            throw  new \Exception("Invalid xml  template") ;
        }


        foreach ($xml->children() as $tag) {
            $name =strtolower((string)$tag->getName());
            if(in_array($name, $allowed)==false) {
                throw  new \Exception("Invalid tag ".$name) ;
            }


            $val =  (string)$tag;
    //        $val = str_replace("'","`",$val) ;
    //        $val = str_replace("\"","`",$val) ;
            $pr->handletag($name, $val, $tag) ;

        }


        $buf = $pr->getBuffer() ;

        return $buf;
    }
  
    private function handletag($name, $val, $tag) {


        if($name==='text') {
            $this->text($val)  ;
            return  ;
        }
        if($name==='beep') {
            $this->beep()  ;
            return  ;
        }
        if($name==='newline') {
            $this->newline()  ;
            return   ;
        }
        if($name==='color') {
            $this->color($val)  ;
            return   ;
        }
        if($name==='cash') {
            $this->cash($val)  ;
            return   ;
        }
        if($name==='cut') {
            $this->cut()  ;
            return   ;
        }
        if($name==='partialcut') {
            $this->cut(true)  ;
            return   ;
        }
        if($name==='separator') {
            $this->separator($val)  ;
            return   ;
        }
        if($name==='align') {
            if($val==="left") {
                $this->align(self::JUSTIFY_LEFT) ;
            }
            if($val==="center") {
                $this->align(self::JUSTIFY_CENTER) ;
            }
            if($val==="right") {
                $this->align(self::JUSTIFY_RIGHT) ;
            }
            return;
        }

        if($name==='leftmargin') {
           $this->lm($val) ;
            return; 
        }
        if($name==='commands') {
            $bl=[];
            foreach(explode(',', $val) as $b) {
                $b=trim($b);
                if(strlen($b)==0) {
                    return;
                }
                $b=intval($b);
                if($b <0 || $b>255) {
                    return;
                }
                $bl[]=$b;
            }

            $this->addBytes($bl)  ;

            return;
        }


        $attr = [];
        foreach($tag->attributes() as $a => $b) {
            $attr[ strtolower((string)$a)] = strtolower((string)$b);
        }

        if($name==='size') {
            if(!isset($attr['height'])) {
                return;
            }
            if(!isset($attr['width'])) {
                return;
            }

            $this->textSize(intval($attr['width']), intval($attr['height']) ) ;
        }
        if($name==='qrcode') {
            if(isset($attr['size'])) {
                $this->QR($val, intval($attr['size'])) ;
            } else {
                $this->QR($val) ;
            }

            return;
        }
        if($name==='barcode') {
            if(!isset($attr['type'])) {
                $po= \App\System::getOptions('printer')  ;
                $attr['type']  = $po['barcodetype']  ;
                if($attr['type'] =='C128') {
                    $attr['type']  ='code128';
                }
                if($attr['type'] =='C39') {
                    $attr['type']  ='code39';
                }
            }
            if(isset($attr['height'])) {
                $this->barcodeHeight($attr['height']) ;
            } else {
                $this->barcodeHeight() ;
            }
            if(isset($attr['width'])) {
                $this->barcodeWidth($attr['width']) ;
            } else {
                $this->barcodeWidth() ;
            }

            if($attr['type'] ==='code128') {
                $this->BarCode($val, self::BARCODE_CODE128) ;
            }
            if($attr['type'] ==='code29') {
                $this->BarCode($val, self::BARCODE_CODE39) ;
            }
            if($attr['type'] ==='ean13') {
                $this->BarCode($val, self::BARCODE_EAN13) ;
            }

            return;
        }

        if($name==='font') {
            $m=0;
            if($val=="b") {
                $m = $m | self::MODE_FONT_B;
            }
            if(($attr['bold'] ?? false)=="true") {
                $m = $m | self::MODE_EMPHASIZED ;
            }

            $this->mode($m) ;
        }
        if($name==='row') {
            $rowtext = "";
            $cols=[];
            $l=0;

            foreach ($tag->children() as $col) {
                $name =strtolower((string)$col->getName());
                if($name != "col") {
                    throw  new \Exception("Invalid tag {$name} in row ") ;
                }


                $coltext =  (string)$col;
                $attr = [];
                foreach($col->attributes() as $a => $b) {
                    $attr[ strtolower((string)$a)] = strtolower((string)$b);
                }

                $cl =  intval($attr['length']);
                if($cl < 1) {
                    throw  new \Exception("Invalid length  of row ") ;
                }
                $l += $cl;

                $cols[]=array("text"=>$coltext,"length"=>$cl,"align"=>$attr['align']??'' );


            }
            if($l > $this->wc) {
                throw  new \Exception("Invalid length  of row ") ;
            }
            $out = "";
            foreach($cols  as  $c) {
                if(mb_strlen($c["text"])>$c['length']) {
                    $c["text"] = mb_substr($c["text"], 0, $c['length']) ;
                }
                if(mb_strlen($c["text"])<$c['length']) {
                    $diff = $c['length'] -  mb_strlen($c["text"]);
                    if($c["align"]=='right') {
                        $c["text"] =  str_repeat(' ', $diff) .$c["text"]  ;
                    } else {
                        $c["text"] =  $c["text"]  .  str_repeat(' ', $diff) ;
                    }

                }

                $out .=  $c["text"];

            }

            $this->text($out) ;


        }


    }


    public static function arr2comm($arr) {

        $pr = new \App\Printer(true) ;

        foreach($arr as $row)  {
            
    //украинское i на  ангглийсккое  хз  почему
        $row = str_replace("і", "i", $row);
        $row = str_replace("І", "I", $row);
              
            
            $pr->labelrow($row);
        }


        $buf = $pr->getBuffer() ;

        return $buf;
    }
     
}
