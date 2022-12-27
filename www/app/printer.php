<?php
namespace App;

/**
* класс для  формирования  ESC/POS команд
*/
class Printer{

    /**
     * ASCII null control character
     */
    const NUL = 0x00;

    /**
     * ASCII linefeed control character
     */
    const LF = 0x0a;

    /**
     * ASCII escape control character
     */
    const ESC = 0x1b;

    /**
     * ASCII form separator control character
     */
    const FS = 0x1c;

    /**
     * ASCII form feed control character
     */
    const FF = 0x0c;

    /**
     * ASCII group separator control character
     */
    const GS = 0x1d;

    /**
     * ASCII data link escape control character
     */
    const DLE = 0x10;

    /**
     * ASCII end of transmission control character
     */
    const EOT = 0x04;

 

    /**
     * Indicates JAN13 barcode when used with Printer::barcode
     */
    const BARCODE_EAN13 = 67;

 

    /**
     * Indicates CODE39 barcode when used with Printer::barcode
     */
    const BARCODE_CODE39 = 69;

 

    /**
     * Indicates CODE128 barcode when used with Printer::barcode
     */
    const BARCODE_CODE128 = 73;

    /**
     * Indicates that HRI (human-readable interpretation) text should not be
     * printed, when used with Printer::setBarcodeTextPosition
     */
    const BARCODE_TEXT_NONE = 0;

    /**
     * Indicates that HRI (human-readable interpretation) text should be printed
     * above a barcode, when used with Printer::setBarcodeTextPosition
     */
    const BARCODE_TEXT_ABOVE = 1;

    /**
     * Indicates that HRI (human-readable interpretation) text should be printed
     * below a barcode, when used with Printer::setBarcodeTextPosition
     */
    const BARCODE_TEXT_BELOW = 2;

    /**
     * Use the first color (usually black), when used with Printer::setColor
     */
    const COLOR_1 = 0;

    /**
     * Use the second color (usually red or blue), when used with Printer::setColor
     */
    const COLOR_2 = 1;

    /**
     * Make a full cut, when used with Printer::cut
     */
    const CUT_FULL = 65;

    /**
     * Make a partial cut, when used with Printer::cut
     */
    const CUT_PARTIAL = 66;

    /**
     * Use Font A, when used with Printer::setFont
     */
    const FONT_A = 0;

    /**
     * Use Font B, when used with Printer::setFont
     */
    const FONT_B = 1;

    /**
     * Use Font C, when used with Printer::setFont
     */
    const FONT_C = 2;

    /**
     * Use default (high density) image size, when used with Printer::graphics,
     * Printer::bitImage or Printer::bitImageColumnFormat
     */
    const IMG_DEFAULT = 0;

    /**
     * Use lower horizontal density for image printing, when used with Printer::graphics,
     * Printer::bitImage or Printer::bitImageColumnFormat
     */
    const IMG_DOUBLE_WIDTH = 1;

    /**
     * Use lower vertical density for image printing, when used with Printer::graphics,
     * Printer::bitImage or Printer::bitImageColumnFormat
     */
    const IMG_DOUBLE_HEIGHT = 2;

    /**
     * Align text to the left, when used with Printer::setJustification
     */
    const JUSTIFY_LEFT = 0;

    /**
     * Center text, when used with Printer::setJustification
     */
    const JUSTIFY_CENTER = 1;

    /**
     * Align text to the right, when used with Printer::setJustification
     */
    const JUSTIFY_RIGHT = 2;

    /**
     * Use Font A, when used with Printer::selectPrintMode
     */
    const MODE_FONT_A = 0;

    /**
     * Use Font B, when used with Printer::selectPrintMode
     */
    const MODE_FONT_B = 1;

    /**
     * Use text emphasis, when used with Printer::selectPrintMode
     */
    const MODE_EMPHASIZED = 8;

    /**
     * Use double height text, when used with Printer::selectPrintMode
     */
    const MODE_DOUBLE_HEIGHT = 16;

    /**
     * Use double width text, when used with Printer::selectPrintMode
     */
    const MODE_DOUBLE_WIDTH = 32;

    /**
     * Underline text, when used with Printer::selectPrintMode
     */
    const MODE_UNDERLINE = 128;

    /**
     * Indicates standard PDF417 code
     */
    const PDF417_STANDARD = 0;

    /**
     * Indicates truncated PDF417 code
     */
    const PDF417_TRUNCATED = 1;

    /**
     * Indicates error correction level L when used with Printer::qrCode
     */
    const QR_ECLEVEL_L = 0;

    /**
     * Indicates error correction level M when used with Printer::qrCode
     */
    const QR_ECLEVEL_M = 1;

    /**
     * Indicates error correction level Q when used with Printer::qrCode
     */
    const QR_ECLEVEL_Q = 2;

    /**
     * Indicates error correction level H when used with Printer::qrCode
     */
    const QR_ECLEVEL_H = 3;

    /**
     * Indicates QR model 1 when used with Printer::qrCode
     */
    const QR_MODEL_1 = 1;

    /**
     * Indicates QR model 2 when used with Printer::qrCode
     */
    const QR_MODEL_2 = 2;

    /**
     * Indicates micro QR code when used with Printer::qrCode
     */
    const QR_MICRO = 3;

 

    /**
     * Indicates no underline when used with Printer::setUnderline
     */
    const UNDERLINE_NONE = 0;


  
    private $buffer=[];
    private $wc=32;
  
    function __construct( ) {
   
       // $options = \App\System::getOptions('printer')  ;
        $user = \App\System::getUser() ;
        
        $this->wc = intval($user->pwsym) ;
        if($this->wc==0) $this->wc=32;
        
        $this->buffer[] = self::ESC;        
        $this->buffer[] = ord('@'); 
        
        $this->addBytes([27,116,17]); //866
    }
  
    private function encode($text){
       // $text = \Normalizer::normalize($text);
        if ($text === false) {
            throw new \Exception("Input must be UTF-8");
        }   
        //украинское i на  ангглийсккое  хз  почему
        $text = str_replace("і","i",$text);  
        $text = str_replace("І","I",$text);  
        $text = mb_convert_encoding($text,  "cp866","utf-8");   
//        $text = iconv('UTF-8','cp866',$text)  ;        
        
        return $text;
    }
    public function getBuffer(){
        return $this->buffer;        
    }
    public function addBytes(array $bytes){
        foreach($bytes as $b){
           $this->buffer[]= $b;   
        }

    }
    
 
    public function align($align )
    {
        $this->buffer[]= 0x1b; 
        $this->buffer[]= 0x61; 
        $this->buffer[]= $align; 
 
    } 
    public function newline( )
    {
        $this->buffer[]= self::LF; 
 
    } 
    public function beep( )
    {
        $this->buffer[]= self::ESC; 
        $this->buffer[]= ord('B'); 
        $this->buffer[]= 2; 
        $this->buffer[]= 1; 
 
    } 
    public function cut($partial = false )
    {
        if($partial)
            $this->buffer[]= self::CUT_PARTIAL; 
        else     
            $this->buffer[]= self::CUT_FULL; 
 
    } 

    /**
    * открыть ящик .  Пин  2 или  5 
    * 
    * @param mixed $pin
    */
    public function cash($pin)
    {
        if($pin==2 || $pin==5) {
            $this->buffer[]=self::ESC; 
            $this->buffer[]= 0x70; 

            $this->buffer[]= $pin == 2 ? 0:1; 
        }
 
    } 
    
    /**
    * вывод текста
    * 
    * @param mixed $text
    * @param mixed $newline   перевод на  новую  строку
    */
    public function text($text,$newline=true )
    {
        if(strlen($text)==0) return;
        $text = $this->encode($text)  ;

        $a = str_split($text,$this->wc) ;    
        $i=0;
        foreach($a as $ap) {
            
            $t = str_split( $ap) ;    
            
            foreach($t as $b) {
               $this->buffer[]= ord($b);     
            }
            $i++;
            
            if($i<count($a) ) {
                $this->newline( )  ;
            }
            
        }
        
        
        if($newline) $this->newline( )  ;
    }      

    /**
    * разделительЮ строка  симолов по  всей  ширине
    * 
    * @param mixed $symbol
    * @return mixed
    */
    public function separator($symbol) {
       if(strlen($symbol) !=1)  return;
       
       $text=str_repeat($symbol,$this->wc) ;
       
       $this->text($text) ;
    }

    /**
    * вывод QR кода   
    * 
    * @param mixed $text
    * @param mixed $type     EAN13 Code128 Code39
    */
    public function QR($text,int $size=12 )
    {
        
        if($size <1 || $size >24 )  {
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
    public function BarCode($text,int $type )
    {
        if($type == self::BARCODE_CODE128) {
            
            $text = "{B".$text;
            
            if (preg_match("/^\{[A-C][\\x00-\\x7F]+$/", $text) === 0) {
                throw new \Exception("invalid barcode {$text}");
            }  
            
            $this->addBytes( [self::GS , ord('k') , $type , (int)strlen($text) ] );
            
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
            
            $this->addBytes( [self::GS , ord('k') , $type , (int)strlen($text) ] );
            
            $t = str_split($text) ;    
            foreach($t as $b) {
               $this->buffer[]= ord($b);     
            }         
            
            
        }
  
        if($type == self::BARCODE_EAN13) {
            
             
            if (preg_match("/^[0-9]{12,13}$/", $text) === 0) {
                throw new \Exception("invalid barcode {$text}");
            }  
            
            $this->addBytes( [self::GS , ord('k') , $type , (int)strlen($text) ] );
            
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
    public function barcodeHeight(int $height = 100)
    {
        if($width < 1 || $width > 255) return;
        $this->addBytes( [self::GS , ord('h') ,   $height ] );
               
    }

    /**
    * ширина  штрихкода   2 - 6
    * 
    * @param mixed $width
    */
    public function barcodeWidth(int $width = 2)
    {
        if($width < 2 || $width > 6) return;
        $this->addBytes( [self::GS , ord('w') ,   $width ] );
    }   
   
      /**
      * цвет
      * 
      * @param mixed $color   0,1
      */
      public function color(int $color )
      {
        if($color == 0  || $color ==1) {
           $this->addBytes([self::ESC , ord('r') , $color])  ;    
        } 
        
 
      }  
      /**
      * режим печати
      * 
      * @param mixed $mode  MODE_FONT_A MODE_FONT_B  self::MODE_EMPHASIZED   self::MODE_DOUBLE_HEIGHT self::MODE_DOUBLE_WIDTH  self::MODE_UNDERLINE
      */
      public function mode($mode=self::MODE_FONT_A )
      {
          
        $allModes = Printer::MODE_FONT_B | self::MODE_EMPHASIZED | self::MODE_DOUBLE_HEIGHT | self::MODE_DOUBLE_WIDTH | self::MODE_UNDERLINE;
        if (!is_integer($mode) || $mode < 0 || ($mode & $allModes) != $mode) {
            throw new \Exception("Invalid mode");
        }

       
        $this->addBytes([self::ESC , ord('!') , $mode])  ;
 
      }  
     
     /**
     * размер  текста
     *  
     * @param mixed $widthMultiplier   1 - 8
     * @param mixed $heightMultiplier  1 - 8
     */
     public function textSize(int $widthMultiplier, int $heightMultiplier)
     {
        if($widthMultiplier < 1 ||  $widthMultiplier >8) $widthMultiplier=1;
        if($heightMultiplier < 1 ||  $heightMultiplier >8) $heightMultiplier=1;
        $c = (2 << 3) * ($widthMultiplier - 1) + ($heightMultiplier - 1);
        $this->addBytes([self::GS , ord('!') , $c])  ;
 
     }   
     
     /**
     * генерит  последовательность  команд по  xml
     * 
     */
     public static function xml2comm($xml){
     
         $pr = new \App\Printer() ;
  
         $allowed = ['size','separator','align','barcode','qrcode','text','font','newline','cash','cut','partialcut','beep','color','commands','row'];
         
         $xml = "<root>{$xml}</root>" ;
         $xml = @simplexml_load_string($xml) ;
         if($xml==false) {
            throw  new \Exception("Invalid xml  template") ; 
         }
         
                          
              foreach ($xml->children() as $tag) {
                $name =strtolower( (string)$tag->getName() );
                if(in_array($name,$allowed)==false)  {
                    throw  new \Exception("Invalid tag ".$name) ;                 
                }
                
                
                $val =  (string)$tag;
                $pr->handletag($name,$val,$tag) ;

              }       
                 
          
            $buf = $pr->getBuffer() ;
             
            return $buf;
     }
     private   function handletag($name,$val,$tag ){


            if($name==='text') {
                $this->text($val)  ;  return  ;
            }
            if($name==='beep') {
                $this->beep()  ;  return  ;
            }
            if($name==='newline') {
                $this->newline()  ;  return   ;
            }
            if($name==='color') {
                $this->color($val)  ;  return   ;
            }
            if($name==='cash') {
                $this->cash($val)  ;  return   ;
            }
            if($name==='cut') {
                $this->cut()  ;  return   ;
            }
            if($name==='partialcut') {
                $this->cut(true)  ;  return   ;
            }
            if($name==='separator') {
                $this->separator($val)  ;  return   ;
            }
            if($name==='align') {
                if($val==="left")   $this->align(self::JUSTIFY_LEFT) ; 
                if($val==="center") $this->align(self::JUSTIFY_CENTER) ; 
                if($val==="right")  $this->align(self::JUSTIFY_RIGHT) ; 
                return;
            }
         
            if($name==='commands') {
                $bl=[];
                foreach( explode(',',$val) as $b){
                    $b=trim($b);
                    if(strlen($b)==0)  return;
                    $b=intval($b);
                    if($b <0 || $b>255)  return;
                    $bl[]=$b;
                } ;
                
                $this->addBytes($bl)  ;
                
                return;
            }
       
         
            $attr = [];
            foreach( $tag->attributes() as $a => $b ){
               $attr[ strtolower( (string)$a)] = strtolower( (string)$b);    
            }
            
            if($name==='size') {
               if(!isset($attr['height'])) return;
               if(!isset($attr['width'])) return;
    
               $this->textSize($attr['width'],$attr['height']) ;
            }
            if($name==='qrcode') {
                if(isset($attr['size'])) {
                   $this->QR($val,intval($attr['size'])) ;                 
                }   else {
                   $this->QR($val) ;                    
                }

                return;
            }
            if($name==='barcode') {
                if(!isset($attr['type'])) {
                    $po= \App\System::getOptions('printer')  ;
                    $attr['type']  = $po['barcodetype']  ;
                    if($attr['type'] =='C128') $attr['type']  ='code128';
                    if($attr['type'] =='C39') $attr['type']  ='code39';
                }
                if(isset($attr['height'])) {
                    $this->barcodeHeight($attr['height']) ;
                }  else {
                    $this->barcodeHeight() ;                    
                }
                if(isset($attr['width'])) {
                    $this->barcodeWidth($attr['width']) ;
                } else {
                    $this->barcodeWidth() ;
                }
                
                if($attr['type'] ==='code128') $this->BarCode($val,self::BARCODE_CODE128) ;
                if($attr['type'] ==='code29') $this->BarCode($val,self::BARCODE_CODE39) ;
                if($attr['type'] ==='ean13') $this->BarCode($val,self::BARCODE_EAN13) ;
                
                return;
            }
   
            if($name==='font') {
               $m=0;
               if($val=="b") $m = $m | self::MODE_FONT_B;
               if($attr['bold']=="true") $m = $m | self::MODE_EMPHASIZED ;
               
               $this->mode($m) ;
            }
            if($name==='row') {
                $rowtext = ""; 
                $cols=[];
                $l=0;

                foreach ($tag->children() as $col) {
                    $name =strtolower( (string)$col->getName() );
                    if($name != "col")  {
                        throw  new \Exception("Invalid tag {$name} in row ") ;                 
                    }
                    
                    
                    $coltext =  (string)$col;
                    $attr = [];
                    foreach( $col->attributes() as $a => $b ){
                       $attr[ strtolower( (string)$a)] = strtolower( (string)$b);    
                    }
                    
                    $cl =  intval($attr['length']);
                    if($cl < 1  ) {
                         throw  new \Exception("Invalid length  of row ") ;                                     
                    }                    
                    $l += $cl;
                    
                    $cols[]=array("text"=>$coltext,"length"=>$cl,"align"=>$attr['align'] );
                    
 
                }   
                if(  $l > $this->wc )  {
                     throw  new \Exception("Invalid length  of row ") ;                                     
                }
                $out = "";
                foreach($cols  as  $c){
                    if(mb_strlen($c["text"])>$c['length']) {
                       $c["text"] = mb_substr( $c["text"],0,$c['length']) ;
                    }  
                    if(mb_strlen($c["text"])<$c['length']) {
                       $diff = $c['length'] -  mb_strlen($c["text"]);
                       if($c["align"]=='right' ) {
                           $c["text"] =  str_repeat(' ',$diff) .$c["text"]  ;                             
                       }  else {
                           $c["text"] =  $c["text"]  .  str_repeat(' ',$diff) ;   
                       }   
                       
                    }  
                    
                    $out .=  $c["text"];
                    
                }
                
                $this->text($out) ;                    
                 
                         
            }

    
     }
 
               
}  
 