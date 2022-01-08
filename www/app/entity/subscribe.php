<?php

namespace App\Entity;

use App\Helper as H;
use App\System;

/**
 * Класс-сущность  подписка  на  событие
 *
 * @table=subscribes
 * @keyfield=sub_id
 */
class Subscribe extends \ZCL\DB\Entity
{

    //типы  событий
    const EVENT_DOCSTATE = 1;
    //типы сообщений
    const MSG_NOTIFY = 1;
    const MSG_EMAIL  = 2;
    const MSG_SMS    = 3;
    const MSG_VIBER  = 4;
    //типы  получателей
    const RSV_CUSTOMER  = 1;
    const RSV_DOCAUTHOR = 2;
    const RSV_USER      = 3;

    protected function init() {
        $this->sub_id = 0;
    }

    protected function afterLoad() {

        $xml = @simplexml_load_string($this->detail);

        $this->sub_typename = (string)($xml->sub_typename[0]);
        $this->reciever_typename = (string)($xml->reciever_typename[0]);
        $this->msg_typename = (string)($xml->msg_typename[0]);
        $this->statename = (string)($xml->statename[0]);
        $this->doctypename = (string)($xml->doctypename[0]);
        $this->msgsubject = (string)($xml->msgsubject[0]);
        $this->username = (string)($xml->username[0]);
        $this->user_id = (int)($xml->user_id[0]);
        $this->state = (int)($xml->state[0]);
        $this->doctype = (int)($xml->doctype[0]);

        parent::afterLoad();
    }

    protected function beforeSave() {
        parent::beforeSave();

        $this->detail = "<detail>";

        $this->detail .= "<sub_typename>{$this->docmetaname}</sub_typename>";
        $this->detail .= "<reciever_typename>{$this->reciever_typename}</reciever_typename>";
        $this->detail .= "<msg_typename>{$this->msg_typename}</msg_typename>";
        $this->detail .= "<user_id>{$this->user_id}</user_id>";
        $this->detail .= "<state>{$this->state}</state>";
        $this->detail .= "<doctype>{$this->doctype}</doctype>";
        $this->detail .= "<doctypename>{$this->doctypename}</doctypename>";
        $this->detail .= "<statename>{$this->statename}</statename>";
        $this->detail .= "<username>{$this->username}</username>";
        $this->detail .= "<msgsubject>{$this->msgsubject}</msgsubject>";

        $this->detail .= "</detail>";

        return true;
    }

    public static function getEventList() {
        $list = array();
        $list[self::EVENT_DOCSTATE] = H::l("sb_docstate");

        return $list;
    }

    public static function getMsgTypeList() {
        $list = array();
        $list[self::MSG_NOTIFY] = H::l("sb_msgnotify");
        $list[self::MSG_EMAIL] = H::l("sb_msgemail");
        $list[self::MSG_SMS] = H::l("sb_msgsms");
        //  $list[self::MSG_VIBER]=  H::l("sb_msgviber");

        return $list;
    }

    public static function getRecieverList() {
        $list = array();
        $list[self::RSV_CUSTOMER] = H::l("sb_rsvcust");
        $list[self::RSV_DOCAUTHOR] = H::l("sb_rsvda");
        $list[self::RSV_USER] = H::l("sb_rsvuser");

        return $list;
    }

    //изменение  состояние  документа
    public static function onDocumentState($doc_id, $state) {
        $doc = \App\Entity\Doc\Document::load($doc_id);

        $list = self::find('disabled <> 1 and sub_type= ' . self::EVENT_DOCSTATE);
        foreach ($list as $sub) {
            if ($sub->doctype != $doc->meta_id) {
                continue;
            }
            if ($sub->state != $state) {
                continue;
            }

            $cnt = $doc->checkStates(array($state));
            if ($cnt > 1) {
                continue;
            }

            $phone = '';
            //  $viber='';
            $email = '';
            $notify = 0;
            if ($sub->reciever_type == self::RSV_CUSTOMER) {
                $c = \App\Entity\Customer::load($doc->customer_id);
                if ($c != null && $c->nosubs != 1) {
                    $phone = $c->phone;
                    // $viber = $c->viber;
                    $email = $c->email;
                }
            }
            if ($sub->reciever_type == self::RSV_DOCAUTHOR) {
                $u = \App\Entity\User::load($doc->user_id);
                if ($u != null) {
                    $phone = $u->phone;
                    //   $viber = $u->viber;
                    $email = $u->email;
                    $notify = $doc->user_id;
                }
            }
            if ($sub->reciever_type == self::RSV_USER) {
                $u = \App\Entity\User::load($sub->user_id);
                if ($u != null) {
                    $phone = $u->phone;
                    //  $viber = $u->viber;   
                    $email = $u->email;
                    $notify = $doc->user_id;
                }
            }
            $text = $sub->getText($doc);
            if (strlen($phone) > 0 && $sub->msg_type == self::MSG_SMS) {
                self::sendSMS($phone, $text);
            }
            if (strlen($email) > 0 && $sub->msg_type == self::MSG_EMAIL) {
                self::sendEmail($email, $text, $sub->msgsubject);
            }
            //   if(strlen($viber)>0 && $sub->msg_type == self::MSG_VIBER) {
            //      self::sendViber($viber,$text) ;
            //   }
            if ($notify > 0 && $sub->msg_type == self::MSG_NOTIFY) {
                self::sendNotify($notify, $text);
            }
        }
    }

    /**
     * возвращает текст  с  учетом разметки
     *
     * @param mixed $doc
     */
    public function getText($doc) {
        //в  разметке  одинарные
        $this->msgtext = str_replace('{', '{{', $this->msgtext);
        $this->msgtext = str_replace('}', '}}', $this->msgtext);

        $header = array();

        $header['document_number'] = $doc->document_number;
        $header['document_date'] = \App\Helper::fd($doc->document_date);
        $header['amount'] = \App\Helper::fa($doc->amount);
        $header['forpay'] = \App\Helper::fa($doc->payamount);
        $header['customer_name'] = $doc->customer_name;
        $header['nal'] = '';
        $header['mf'] = '';
        $header['pos'] = '';
        $header['source'] = '';
        $header['payed'] = '';
        $header['credit'] = '';
        $header['device'] = $doc->headerdata['device'];
        if (strlen($doc->headerdata['device']) > 0 && strlen($doc->headerdata['devsn']) > 0) {
            $header['device'] .= " (" . $doc->headerdata['devsn'] . ")";
        }

        $header['notes'] = $doc->document_number;

        if ($doc->headerdata['payment'] > 0) {
            $mf = \App\Entity\MoneyFund::load($doc->headerdata['payment']);
            $header['mf'] = $mf->mf_name;
            if ($mf->beznal == 1) {
                $header['nal'] = \App\Helper::l("cbeznal");
            } else {
                $header['nal'] = \App\Helper::l("cnal");
            }
        } else {
            if ($doc->headerdata['payamount'] > 0 && $doc->headerdata['payed'] == 0) {
                $header['mf'] = H::l("credit");
            }
            if ($doc->headerdata['payamount'] == 0) {
                $header['mf'] = H::l("prepaid");
            }
        }
        if ($doc->headerdata['payed'] == 0 && $doc->payamount > 0) {
            $header['mf'] = \App\Helper::l("credit");
        }
        if ($doc->headerdata['payed'] == 0 && $doc->payamount == 0) {
            $header['mf'] = \App\Helper::l("prepaid");
        }
        if ($doc->headerdata['payed'] > 0) {
            $header['payed'] = \App\Helper::fa($doc->headerdata['payed']);
        }

        if ($doc->headerdata['pos']) {
            $pos = \App\Entity\Pos::load($doc->headerdata['pos']);
            $header['pos'] = $pos->pos_name;
        }
        if ($doc->headerdata['salesource'] > 0) {
            $sl = H::getSaleSources();
            $header['source'] = $sl[$doc->headerdata['salesource']];
        }
        if ($doc->customer_id > 0) {
            $cust = \App\Entity\Customer::load($doc->customer_id) ;
            $dolg = $cust->getDolg();
            if($dolg >0) {
               $header['credit'] = \App\Helper::fa($dolg);    
            }
            
        }
        $header['taxurl'] = $doc->getFiscUrl();

        $table = array();
        foreach ($doc->unpackDetails('detaildata') as $item) {
            $table[] = array('item_name'    => $item->itemname,
                             'item_code'    => $item->item_code,
                             'item_barcode' => $item->bar_code,
                             'msr'          => $item->msr,
                             'qty'          => \App\Helper::fqty($item->quantity),
                             'price'        => \App\Helper::fa($item->price),
                             'summa'        => \App\Helper::fa($item->price * $item->quantity)
            );
        }


        $header['list'] = $table;

        try {
            $m = new \Mustache_Engine();
            $text = $m->render($this->msgtext, $header);

            return $text;
        } catch(\Exception $e) {
            return "Ошибка  разметки";
        }
    }

    public static function sendEmail($email, $text, $subject) {
        $common = System::getOptions("common");

        H::sendLetter($text, '', $email, $subject);
    }

    public static function sendViber($viber, $text) {

    }

    public static function sendNotify($user_id, $text) {
        $n = new \App\Entity\Notify();
        $n->user_id = $user_id;
        $n->sender_id = \App\Entity\Notify::SUBSCRIBE;
        $n->message = $text;

        $n->save();
    }

    public static function sendSMS($phone, $text, $viber = false) {
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
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                $output = curl_exec($curl);
                if (curl_errno($curl) > 0) {

                    return 'Curl error: ' . curl_error($curl);
                }
                curl_close($curl);
                $output = json_decode($output, true);
                if ($output['code'] <> 0) {
                    \App\Helper::logerror($output['error']);
                    return $output['error'];
                } else {
                    return '';
                }
            }
            if ($sms['smstype'] == 2) {  //turbo sms
                $json = '{
               "recipients":[
                  "' . $phone . '" 
               ],
               "sms":{
                  
                  "text": "' . $text . '"
               }
            } ';

                $url = "https://api.turbosms.ua/message/send.json";
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_USERPWD, $sms['turbosmstoken']);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
                $output = curl_exec($curl);
                if (curl_errno($curl) > 0) {

                    return 'Curl error: ' . curl_error($curl);
                }
                curl_close($curl);
                $output = json_decode($output, true);
                if ($output['response_code'] <> 0) {
                    \App\Helper::logerror($output['response_status']);
                    return $output['response_status'];
                } else {
                    return '';
                }
            }
            if ($sms['smstype'] == 3) {  //sms  fly
                // $text = iconv('windows-1251', 'utf-8', htmlspecialchars('Заметьте, что когда герой фильма подписывает договор с Сатаной, он не подписывает копию договора и не получает ее.'));
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
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_URL, 'http://sms-fly.com/api/api.php');
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
                \App\Helper::logerror($response);
                return $response;
            }
        } catch(\Exception $e) {
            \App\Helper::logerror($e->getMessage());
            return $e->getMessage();
        }
    }

}
