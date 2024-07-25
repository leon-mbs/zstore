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
        $this->edrpou = (string)($xml->edrpou[0]);
        $this->firstname = (string)($xml->firstname[0]);
        $this->lastname = (string)($xml->lastname[0]);
        $this->chat_id = (string)($xml->chat_id[0]);

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
     * @param mixed $edrpou
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

        if (is_array($options['leadsources']) == false) {
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

        if (is_array($options['leadstatuses']) == false) {
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
        $sql = "select coalesce(sum(bonus),0) as bonus from paylist where  document_id in (select  document_id  from  documents where  customer_id={$this->customer_id})";

        return $conn->GetOne($sql);

    }
    /**
    *  список  бонусов по  контрагентам
    *
    */
    public static function getBonusAll() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(sum(bonus),0) as bonusall, d.customer_id from paylist p join documents d ON  p.document_id = d.document_id group by  d.customer_id ";
        $ret = array();
        foreach($conn->Execute($sql) as $row) {
            if(doubleval($row['bonusall']) <>0) {
                $ret[$row['customer_id']] = $row['bonusall'] ;
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
        $sql = "select bonus, paydate,d.document_number  from paylist p join documents d ON  p.document_id = d.document_id where d.customer_id={$this->customer_id} and coalesce(p.bonus,0) <> 0 order  by  pl_id ";
        $ret = array();
        foreach($conn->Execute($sql) as $row) {

            $b = new \App\DataItem() ;
            $b->paydate = strtotime($row['paydate']) ;
            $b->document_number = $row['document_number']  ;
            $b->bonus = $row['bonus']  ;

            $ret[]=$b;
        }
        return $ret;

    }

    public function getDolg() {

        $dolg = 0;
        $conn = \ZDB\DB::getConnect();

        $where  =  "     customer_id = {$this->customer_id}   and    state NOT IN (0, 1, 2, 3, 15, 8, 17) ";
        $bal=0;
        foreach (\App\Entity\Doc\Document::findYield($where, "document_date asc,document_id asc ", -1, -1) as $d) {
           
            $ch = Customer::balans($d );
         
            if($ch===true) {
                continue;
            }
            
            $diff = $ch['passive'] - $ch['active'];

            $bal +=  $diff;
           
                
        } 



        return $bal;

    }

    

    /**
    * баланс  по  документу
    * актив   - долг  когтрагента
    * пассиив   - долг  когтрагенту
    */
    public static function balans( \App\Entity\Doc\Document $doc,$ctype=0 ) {
       
       
        if($doc->meta_name=='Order' && $doc->payamount==0 && $doc->payed ==0 ) {
            return  true;
        }
        
      
        $ret=[];
     
        $ret['active']  = 0;
        $ret['passive'] = 0;
 
        
        if($ctype != self::TYPE_SELLER) {
            if( in_array( $doc->meta_name,['GoodsIssue',  'POSCheck', 'OrderFood', 'ServiceAct' ]) ) {
                 $ret['active']=$doc->payamount ?? 0;
                 $ret['passive']=$doc->payed ?? 0;
            }

            if( in_array( $doc->meta_name,['Order']) ) {
                 $ret['passive']=$doc->payed ?? 0;
            }
            if( in_array( $doc->meta_name,['Invoice']) ) {
                 $ret['passive']=$doc->payed ?? 0;
            }
            if( in_array( $doc->meta_name,['TTN']) ) {
                 $ret['active']=$doc->payamount ?? 0;
            }
            if( in_array( $doc->meta_name,['ReturnIssue']) ) {
                 $ret['active']=$doc->payed ?? 0;
                 $ret['passive']=$doc->payamount ?? 0;
                 
            }
            if( in_array( $doc->meta_name,['OutcomeMoney']) && strpos($doc->content,'<detail>1</detail>') > 0) {
                $ret['active']=$doc->payed ?? 0;    //возврат покупателю
            }
            if( in_array( $doc->meta_name,['IncomeMoney']) && strpos($doc->content,'<detail>1</detail>') > 0) {
                 $ret['passive']=$doc->payed ?? 0;    //оплата от покупателя
            }            
        }
        
        if($ctype != self::TYPE_BAYER) {
      
            if( in_array( $doc->meta_name,[ 'GoodsReceipt', 'IncomeService' ]) ) {
                 $ret['passive']=$doc->payamount ?? 0;
                 $ret['active']=$doc->payed ?? 0;
            }
         
            if( in_array( $doc->meta_name,['InvoiceCust']) ) {
                 $ret['active']=$doc->payed ?? 0;
            }
            if( in_array( $doc->meta_name,['RetCustIssue']) ) {
                 $ret['active']=$doc->payamount ?? 0;
                 $ret['passive']=$doc->payed ?? 0;
            }


            if( in_array( $doc->meta_name,['OutcomeMoney']) && strpos($doc->content,'<detail>2</detail>') > 0) {
                 $ret['active']=$doc->payed ?? 0;   //  оплата  поставщику
            }


            if( in_array( $doc->meta_name,['IncomeMoney']) && strpos($doc->content,'<detail>2</detail>') > 0) {
                 $ret['passive']=$doc->payed ?? 0;    //возврат от поставщика
            }
         }
      
 
        return $ret;
        
                 
    }
        
    
    //вместо  промотра  в  бд
    public  static function  get_acc_view(){
        $brdoc = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $brdoc = " and  d.document_id in(select  document_id from  documents dd where dd.branch_id in ({$brids}) )";
        }
        
        
        
       $cust_acc_view = "SELECT
          COALESCE(SUM((CASE WHEN (d.meta_name IN ('InvoiceCust', 'GoodsReceipt', 'IncomeService')) THEN d.payed WHEN ((d.meta_name = 'OutcomeMoney') AND
              (d.content LIKE '%<detail>2</detail>%')) THEN d.payed WHEN (d.meta_name = 'RetCustIssue') THEN d.payamount ELSE 0 END)), 0) AS s_active,
          COALESCE(SUM((CASE WHEN (d.meta_name IN ('IncomeService', 'GoodsReceipt')) THEN d.payamount WHEN ((d.meta_name = 'IncomeMoney') AND
              (d.content LIKE '%<detail>2</detail>%')) THEN d.payed WHEN (d.meta_name = 'RetCustIssue') THEN d.payed ELSE 0 END)), 0) AS s_passive,
          COALESCE(SUM((CASE WHEN (d.meta_name IN ('GoodsIssue', 'TTN', 'PosCheck', 'OrderFood', 'ServiceAct')) THEN d.payamount WHEN ((d.meta_name = 'OutcomeMoney') AND
              (d.content LIKE '%<detail>1</detail>%')) THEN d.payed WHEN (d.meta_name = 'ReturnIssue') THEN d.payed ELSE 0 END)), 0) AS b_active,
          COALESCE(SUM((CASE WHEN (d.meta_name IN ('GoodsIssue', 'Order', 'PosCheck', 'OrderFood', 'Invoice', 'ServiceAct')) THEN d.payed WHEN ((d.meta_name = 'IncomeMoney') AND
              (d.content LIKE '%<detail>1</detail>%')) THEN d.payed WHEN (d.meta_name = 'ReturnIssue') THEN d.payamount ELSE 0 END)), 0) AS b_passive,
          d.customer_id AS customer_id
        FROM documents_view d
        WHERE d.state NOT IN (0, 1, 2, 3, 15, 8, 17)
        AND d.customer_id > 0 {$brdoc}
        and d.customer_id in(select c.customer_id from customers c  where  status=0) 

        GROUP BY d.customer_id";      
        
        return $cust_acc_view;
        
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



}
