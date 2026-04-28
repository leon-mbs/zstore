<?php
namespace App;

/**
* класс  с  методами  комуникаций    
*/
class Comm
{
     
    public static function sendEmail($email, $text, $subject, $doc=null) {
        global $_config;

       

        $emailfrom = $_config['smtp']['emailfrom'];
        if(strlen($emailfrom)==0) {
            $emailfrom = $_config['smtp']['user'];

        }
        $filename = '';
        $f = '';
        try {

            if($doc != null) {
                $filename = strtolower($doc->meta_name) . ".pdf";
                $html = $doc->cast()->generateReport();
                $dompdf = new \Dompdf\Dompdf(array('isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans'));
                $dompdf->loadHtml($html);

                $dompdf->render();

                $data = $dompdf->output();

                $f = tempnam(sys_get_temp_dir(), "eml");
                file_put_contents($f, $data);

            }



            $mail = new \PHPMailer\PHPMailer\PHPMailer();

            if ($_config['smtp']['usesmtp'] == true) {
                $mail->isSMTP();
                $mail->Host = $_config['smtp']['host'];
                $mail->Port = $_config['smtp']['port'];
                $mail->Username = $_config['smtp']['user'];
                $mail->Password = $_config['smtp']['pass'];
                $mail->SMTPAuth = true;
                if ($_config['smtp']['tls'] == true) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
            }


            $mail->setFrom($emailfrom);
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->msgHTML($text);
            $mail->CharSet = "UTF-8";
            $mail->IsHTML(true);
            if(strlen($filename)>0) {
                $mail->AddAttachment($f, $filename, 'base64', 'application/pdf');
            }


            if ($mail->send() === false) {
                H::logerror($mail->ErrorInfo) ;
                return "See log";
            } else {
                //  System::setSuccessMsg('E-mail відправлено');
            }
        } catch(\Exception $e) {

            H::logerror($e->getMessage()) ;
            return "See log";

        }
        return '';
    }

    public static function sendViber($phone, $text) {

        $sms = System::getOptions("sms");


        if ($sms['smstype'] == 2) {  // sms club


            $url = 'https://im.smsclub.mobi/vibers/send';

            $data = json_encode([
                'phones' => array($phone),
                'message' => $text,
                'sender' => $sms['smsclubvan']
            ]);

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERPWD => $sms['smsclublogin'] . ':' . $sms['smsclubpass'],
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ]
            ]);


            $response = curl_exec($ch);

            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


            $encoded = json_decode($response, true);
            curl_close($ch);


            if ($httpcode > 200) {
                return "code ".$httpcode . ' ' .$response;
            }

            return  ""  ;



        }
    }

    public static function sendNotify($user_id, $text) {
        $n = new \App\Entity\Notify();
        $n->user_id = $user_id;
        $n->sender_id = \App\Entity\Notify::SUBSCRIBE;
        $n->message = $text;

        $n->save();
    }

    public static function sendBot($chat_id, $text, $doc=null, $ishtml=false) {
        $bot = new \App\ChatBot(\App\System::getOption("common", 'tbtoken')) ;
        $bot->sendMessage($chat_id, $text,$ishtml)  ;
        if($doc!= null) {
            $filename = strtolower($doc->meta_name) . ".pdf";
            $html = $doc->cast()->generateReport();
            $dompdf = new \Dompdf\Dompdf(array('isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans'));
            $dompdf->loadHtml($html);

            $dompdf->render();

            $data = $dompdf->output();

            $f = tempnam(sys_get_temp_dir(), "bot");
            file_put_contents($f, $data);
            $bot->sendDocument($chat_id, $f, $filename) ;
            return '';
        }
    }

    public static function sendSMS($phone, $text) {

        try {
            $sms = System::getOptions("sms");

            if ($sms['smstype'] == 1) {  //semy sms
                $data = array(
                    "phone"  => $phone,
                    "msg"    => $text,
                    "device" => $sms['smssemydevid'],
                    "token"  => $sms['smssemytoken']
                );
                $url = "https://semysms.net/api/3/sms.php";
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                $output = curl_exec($curl);
                if (curl_errno($curl) > 0) {

                    return 'Curl error: ' . curl_error($curl);
                }
                curl_close($curl);
                $output = json_decode($output, true);
                if ($output['code'] <> 0) {

                    return $output['error'];
                } else {
                    return '';
                }
            }

            if ($sms['smstype'] == 2) {  // sms club


                $url = 'https://im.smsclub.mobi/sms/send';

                $data = json_encode([
                    'phone' => array($phone),
                    'message' => $text,
                    'src_addr' => $sms['smscluban']
                ]);

                $ch = curl_init();

                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $sms['smsclubtoken'],
                        'Content-Type: application/json'
                    ]
                ]);


                $response = curl_exec($ch);

                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


                $encoded = json_decode($response, true);
                curl_close($ch);

                if ($httpcode >200) {
                    H::logerror("code ".$httpcode) ;
                    H::logerror($response) ;
                    return "Error. See logs";
                }

                return  ""  ;
            }

            if ($sms['smstype'] == 3) {  //sms  fly

                $an = '';
                if (strlen($sms['flysmsan']) > 0) {
                    $an = "source=\"{$sms['flysmsan']}\"";
                }


                $lifetime = 4; // срок жизни сообщения 4 часа

                $myXML = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
                $myXML .= "<request>" . "\n";
                $myXML .= "<operation>SENDSMS</operation>" . "\n";
                $myXML .= '        <message   lifetime="' . $lifetime . '" ' . $an . ' >' . "\n";
                $myXML .= "        <body>" . $text . "</body>" . "\n";
                $myXML .= "        <recipient>" . $phone . "</recipient>" . "\n";
                $myXML .= "</message>" . "\n";
                $myXML .= "</request>";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_USERPWD, $sms['flysmslogin'] . ':' . $sms['flysmspass']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_URL, 'https://sms-fly.com/api/api.php');
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "Accept: text/xml"));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $myXML);
                $response = curl_exec($ch);

                if (curl_errno($ch) > 0) {

                    return 'Curl error: ' . curl_error($ch);
                }
                curl_close($ch);
                if (strpos($response, 'ACCEPT') > 0) {
                    return '';
                }

                return $response;
            }
            
            if ($sms['smstype'] == 4) { //кастомный код
                $text = str_replace("'","`",$text) ;
                $text = str_replace("\"","`",$text) ;
                if($sms['smscustlang'] == 'js') {
                   \App\Application::$app->getResponse()->addJavaScript("sendSMSCust('{$phone}','{$text}')",true) ;
                }
                if($sms['smscustlang'] == 'php') {
                   $code= ' $phone="'.$phone.'" ; $sms="'.$text.'" ; '  .  base64_decode(  $sms['smscustscript'] ) ;  
                   $ret= eval($code);  
                   if(strlen($ret??'')>0) {
                       \App\System::setErrorMsg($ret)  ;
                   }
                }
            }

        } catch(\Exception $e) {

            return $e->getMessage();
        }
    }

    /**
    * вызов  веб-хука
    * 
    * @param mixed $url
    * @param mixed $post метод
    * @param mixed $text   если метод post
    * @return mixed
    */
    public static function sendHook($url, $post=false, $text="") {

        try {
            
   
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_POST, $post);
                if($post && strlen($text) > 0) {
                   curl_setopt($curl, CURLOPT_POSTFIELDS, $text);                    
                }

                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
     //           $output = curl_exec($curl);
                if (curl_errno($curl) > 0) {

                    return 'Curl error: ' . curl_error($curl);
                }
                $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($status_code >= 300 ) {
                    return 'http code: ' . $status_code;
                }
                if ($status_code == 0 ) {
                    return 'http code:0 ' ;
                }
                 
                
                curl_close($curl);
                return '';

        } catch(\Exception $e) {

            return $e->getMessage();
        }
    }
    
}  
 