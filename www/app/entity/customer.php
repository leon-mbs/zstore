<?php

namespace App\Entity;

/**
 * Класс-сущность  контрагент
 *
 * @table=customers
 * @view=customers_view
 * @keyfield=customer_id
 */
class Customer extends \ZCL\DB\Entity
{
    public const STATUS_ACTUAL   = 0;  //актуальный
    public const STATUS_DISABLED = 1; //не используется
    public const STATUS_LEAD     = 2; //лид

    public const TYPE_BAYER      = 1; //покупатель
    public const TYPE_SELLER     = 2; //поставщик

    protected function init() {
        $this->customer_id = 0;
        $this->customer_name = '';
        $this->status = 0;
        $this->fromlead = 0;
        $this->createdon = time();
    }

    protected function beforeSave() {
        parent::beforeSave();
   
        if ($this->customer_id == 0) { //новый
            $this->createdon = time();
            $this->user_id = \App\System::getUser()->user_id;
        }
        $this->customer_name = str_replace("'","`",$this->customer_name) ;
        $this->customer_name = str_replace("\"","`",$this->customer_name) ;
  
        //упаковываем  данные в detail
        $this->detail = "<detail><code>{$this->code}</code>";
        if (doubleval($this->discount) > 0) {
            $this->detail .= "<discount>{$this->discount}</discount>";
        }
        if (doubleval($this->pbonus) > 0) {
            $this->detail .= "<pbonus>{$this->pbonus}</pbonus>";
        }



        $this->detail .= "<type>{$this->type}</type>";
        $this->detail .= "<fromlead>{$this->fromlead}</fromlead>";
        $this->detail .= "<jurid>{$this->jurid}</jurid>";
        $this->detail .= "<shopcust_id>{$this->shopcust_id}</shopcust_id>";
        $this->detail .= "<isholding>{$this->isholding}</isholding>";
        $this->detail .= "<holding>{$this->holding}</holding>";
        $this->detail .= "<viber>{$this->viber}</viber>";
        $this->detail .= "<telega>{$this->telega}</telega>";
        $this->detail .= "<nosubs>{$this->nosubs}</nosubs>";
        $this->detail .= "<allowedshop>{$this->allowedshop}</allowedshop>";
        $this->detail .= "<edrpou>{$this->edrpou}</edrpou>";

        $this->detail .= "<user_id>{$this->user_id}</user_id>";
        $this->detail .= "<chat_id>{$this->chat_id}</chat_id>";
        $this->detail .= "<pricetype>{$this->pricetype}</pricetype>";

        $this->detail .= "<holding_name><![CDATA[{$this->holding_name}]]></holding_name>";
        $this->detail .= "<firstname><![CDATA[{$this->firstname}]]></firstname>";
        $this->detail .= "<lastname><![CDATA[{$this->lastname}]]></lastname>";
        $this->detail .= "<address><![CDATA[{$this->address}]]></address>";
        $this->detail .= "<addressdel><![CDATA[{$this->addressdel}]]></addressdel>";
        $this->detail .= "<comment><![CDATA[{$this->comment}]]></comment>";
        $this->detail .= "<passw><![CDATA[{$this->passw}]]></passw>";
        $this->detail .= "<npcityref><![CDATA[{$this->npcityref}]]></npcityref>";
        $this->detail .= "<npcityname><![CDATA[{$this->npcityname}]]></npcityname>";
        $this->detail .= "<nppointref><![CDATA[{$this->nppointref}]]></nppointref>";
        $this->detail .= "<nppointname><![CDATA[{$this->nppointname}]]></nppointname>";
        $this->detail .= "</detail>";

  
        
        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        if(strlen($this->detail)==0) {
            return;
        }
        $xml = simplexml_load_string($this->detail);

        $this->discount = doubleval($xml->discount[0]);
        $this->pbonus = doubleval($xml->pbonus[0]);

        $this->type = (int)($xml->type[0]);
        $this->jurid = (int)($xml->jurid[0]);
        $this->shopcust_id = (int)($xml->shopcust_id[0]);
        $this->isholding = (int)($xml->isholding[0]);
        $this->user_id = (int)($xml->user_id[0]);
        $this->pricetype = (string)($xml->pricetype[0]);
        $this->fromlead = (int)($xml->fromlead[0]);

        $this->allowedshop = (int)($xml->allowedshop[0]);
        $this->nosubs = (int)($xml->nosubs[0]);
        $this->holding = (int)($xml->holding[0]);
        $this->holding_name = (string)($xml->holding_name[0]);
        $this->address = (string)($xml->address[0]);
        $this->addressdel = (string)($xml->addressdel[0]);
        $this->comment = (string)($xml->comment[0]);
        $this->viber = (string)($xml->viber[0]);
        $this->telega = (string)($xml->telega[0]);
        $this->edrpou = (string)($xml->edrpou[0]);
        $this->firstname = (string)($xml->firstname[0]);
        $this->lastname = (string)($xml->lastname[0]);
        $this->chat_id = (string)($xml->chat_id[0]);
        $this->passw = (string)($xml->passw[0]);
        $this->npcityref = (string)($xml->npcityref[0]);
        $this->npcityname = (string)($xml->npcityname[0]);
        $this->nppointref = (string)($xml->nppointref[0]);
        $this->nppointname = (string)($xml->nppointname[0]);

        $this->createdon = strtotime($this->createdon ?? '');
        
        parent::afterLoad();
    }

    public function afterSave($update) {
        if($update==false) {
            \App\Entity\Subscribe::onNewCustomer($this->customer_id) ;
        }       
    }
 
    public function beforeDelete() {

        $conn = \ZDB\DB::getConnect();

        $sql = "  select count(*)  from  documents where   customer_id = {$this->customer_id}  ";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0) {
            return  "Контрагент використовується в документах";
        }
        return "";
    }

    protected function afterDelete() {

        $conn = \ZDB\DB::getConnect();

        $conn->Execute("delete from eventlist where   customer_id=" . $this->customer_id);
        $conn->Execute("delete from messages where item_type=" . \App\Entity\Message::TYPE_CUST . " and item_id=" . $this->customer_id);
        $conn->Execute("delete from files where item_type=" . \App\Entity\Message::TYPE_CUST . " and item_id=" . $this->customer_id);
        $conn->Execute("delete from filesdata where   file_id not in (select file_id from files)");
        \App\Entity\Tag::updateTags([],   \App\Entity\Tag::TYPE_CUSTOMER,$this->customer_id) ;

    }

    public static function getByPhone($phone) {
        if (strlen($phone) == 0) {
            return null;
        }
        $conn = \ZDB\DB::getConnect();
        return Customer::getFirst(' phone = ' . $conn->qstr($phone) .' or   phone = ' . $conn->qstr('38'.$phone));
    }

    public static function getByEdrpou($edrpou) {
        $edrpou = trim($edrpou);
        if (strlen($edrpou) == 0) {
            return null;
        }
       
        return Customer::getFirst(' detail like  ' . Customer::qstr("%<edrpou>{$edrpou}</edrpou>%") );
    }
    public static function getByEmail($email) {
        if (strlen($email) == 0) {
            return null;
        }
        $conn = \ZDB\DB::getConnect();
        return Customer::getFirst(' email = ' . $conn->qstr($email));
    }

    /**
     * список  контрагентов  кроме  холдингов
     *
     * @param mixed $search
     * @param mixed $type
     * @param mixed $searchedrpou
     */
    public static function getList($search = '', $type = 0, $searchedrpou = false) {


        $where = "status=0 and detail not like '%<isholding>1</isholding>%' ";
        if (strlen($search) > 0) {
            $edrpou = Customer::qstr('%<edrpou>' . $search . '</edrpou>%');

            $search = Customer::qstr('%' . $search . '%');

            if ($searchedrpou) {
                $where .= " and  (customer_name like {$search}  or phone like {$search}  or email like {$search} or detail like {$edrpou}  ) ";
            } else {
                $where .= " and  (customer_name like {$search}  or phone like {$search}  or email like {$search} ) ";
            }


        }
        if ($type > 0) {
            $where .= " and  (detail like '%<type>{$type}</type>%'  or detail like '%<type>0</type>%' ) ";
        }

        return Customer::findArray("concat(customer_name,' ',phone)", $where, "customer_name");
    }

    public static function getHoldList($type = 0) {

        $conn = \ZDB\DB::getConnect();
        $where = "status=0 and detail like '%<isholding>1</isholding>%' ";
        if ($type > 0) {
            $where .= " and  (detail like '%<type>{$type}</type>%'  or detail like '%<type>0</type>%' ) ";
        }

        return Customer::findArray("customer_name", $where, "customer_name");
    }

    public static function getLeadSources() {
        $options = \App\System::getOptions('common');

        if (is_array($options['leadsources']??null) == false) {
            $options['leadsources'] = array();
        }

        $list = array();
        foreach ($options['leadsources'] as $item) {
            if (strlen($item->name) == 0) {
                continue;
            }
            $list[$item->name] = $item->name;
        }


        return $list;
    }

    public static function getLeadStatuses() {
        $options = \App\System::getOptions('common');

        if (is_array($options['leadstatuses']??null) == false) {
            $options['leadstatuses'] = array();
        }

        $list = array();
        foreach ($options['leadstatuses'] as $item) {
            if (strlen($item->name) == 0) {
                continue;
            }
            $list[$item->name] = $item->name;
        }


        return $list;
    }

    /**
    * начисленные бонусы
    *
    */
    public function getBonus() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(sum(amount),0) as bonus from custacc where  customer_id={$this->customer_id} and optype=1";

        return intval($conn->GetOne($sql) );

    }
    /**
    *  список  бонусов по  контрагентам
    *
    */
    public static function getBonusAll() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(sum(amount),0) as bonusall, customer_id from custacc where optype=1  group by  customer_id ";
        $ret = array();
        foreach($conn->Execute($sql) as $row) {
            if(doubleval($row['bonusall']) <>0) {
                $ret[$row['customer_id']] = intval($row['bonusall'] );
            }

        }
        return $ret;

    }
    /**
    * история бонусов
    *
    */
    public function getBonuses() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select p.amount, p.createdon,p.document_number  from custacc_view p  where p.optype=1 and p.customer_id={$this->customer_id} and coalesce(p.amount,0) <> 0 order  by  ca_id ";
        $ret = array();
        foreach($conn->Execute($sql) as $row) {

            $b = new \App\DataItem() ;
            $b->paydate = strtotime($row['createdon']) ;
            $b->document_number = $row['document_number']  ;
            $b->bonus = intval( $row['amount'] ) ;

            $ret[]=$b;
        }
        return $ret;

    }

    public function getDolg() {

        $dolg = 0;
        $conn = \ZDB\DB::getConnect();

        $sql="select sum(amount) from custacc where optype in (2,3) and  customer_id= ".$this->customer_id; 

        return \App\Helper::fa($conn->GetOne($sql));

    }

    


    public function getDiscount() {
        $d = $this->discount;
        if($d > 0) {
            return  $d;
        }
        $d = 0;
        $disc = \App\System::getOptions("discount");


        $amount = $this->sumAll();

        if ($disc["discsumma1"] > 0 && $disc["disc1"] > 0 && $disc["discsumma1"] < $amount) {
            $d = $disc["disc1"];
        }
        if ($disc["discsumma2"] > 0 && $disc["disc2"] > 0 && $disc["discsumma2"] < $amount) {
            $d = $disc["disc2"];
        }
        if ($disc["discsumma3"] > 0 && $disc["disc3"] > 0 && $disc["discsumma3"] < $amount) {
            $d = $disc["disc3"];
        }
        if ($disc["discsumma4"] > 0 && $disc["disc4"] > 0 && $disc["discsumma4"] < $amount) {
            $d = $disc["disc4"];
        }



        return $d ;
    }


    /**
    * сумма  всех  покупок
    *
    */
    public function sumAll() {
        $conn = \ZDB\DB::getConnect() ;
        $sql= "select sum(amount) from paylist where document_id in (select document_id from documents_view where customer_id = {$this->customer_id} 
               and meta_name in ('GoodsIssue', 'Order', 'PosCheck', 'OrderFood', 'Invoice', 'ServiceAct','ReturnIssue')   ) " ;
        return  doubleval($conn->GetOne($sql));

    }
    /**
    * список  ксли  холдинг
    * 
    */
    public function getChillden() {
        
        if($this->isholding !=1){
           return  [];
        }
        
        
        $conn = \ZDB\DB::getConnect() ;
        $sql= "select customer_id from customers  where status=0 and detail like '%<holding>{$this->customer_id}</holding>%' ";
        return  $conn->GetCol($sql);

    }
    
    public function getID() {
        return $this->customer_id;
    }

    /**
    * сообшения  с  чата
    *
    */
    public function chatMessages() {
        $conn = \ZDB\DB::getConnect() ;



        $sql= "select sum(amount) from paylist where document_id in (select document_id from documents_view where customer_id = {$this->customer_id} 
               and meta_name in ('GoodsIssue', 'Order', 'PosCheck', 'OrderFood', 'Invoice', 'ServiceAct','ReturnIssue')   ) " ;
        return  doubleval($conn->GetOne($sql));

    }

    public static function getConstraint() {
        $user  = \App\System::getUser() ;
        if(($user->custtype??0)  ==0 ){
            return '';
        }
        return "  detail like '%<type>{$user->custtype}</type>%'   ";
    }

}
