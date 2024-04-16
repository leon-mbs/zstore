<?php
define('_ROOT', __DIR__ . '/');  
require_once _ROOT . 'vendor/autoload.php';



$connfrom = \ADONewConnection("postgres");
$connfrom->Connect('localhost','postgres', 'root', 'test1');

$connto = \ADONewConnection("mysqli");
$connto->Connect('localhost','root', 'root', 'zstore2');
$connto->Execute("SET NAMES 'utf8'");
$connto->Execute("SET SQL_BIG_SELECTS=1");


 

function   movedata($table,$dates=[]){
   global  $connfrom , $connto;
   
    $rs = $connfrom->Execute("select * from   {$table}");
    foreach ($rs as $row) {


       foreach($row as $i => $v) {
          if($v==null) {
           unset( $row[$i] );    
          }
          
       }

    
       foreach($dates as $d) {
          if(strlen($row[$d] ??'')>0) {
              $row[$d]  = strtotime($row[$d])  ;    
          }
          
       }
        
        
      $b= $connto->AutoExecute($table, $row, "INSERT");
        
    }     
}    
 
   

  movedata('options') ;   
  movedata('branches') ;   
  movedata('roles') ;   
  movedata('users',['createdon']) ;   
  movedata('metadata') ;   
  movedata('saltypes') ;   
  movedata('firms') ;   
  movedata('mfund') ;   
  movedata('customers',['createdon']) ;   
  movedata('stores') ;       
  movedata('item_cat') ;  
  movedata('items') ;  
  movedata('store_stock') ;  
  movedata('employees') ;  
  movedata('empacc',['createdon']) ;  
  movedata('contracts',['createdon']) ;  
  movedata('documents',['document_date','lastupdate']) ;  
  movedata('docstatelog',['createdon']) ;  
  movedata('services') ;  
  movedata('entrylist',['createdon']) ;  
  movedata('paylist',['paydate']) ;  
  movedata('timesheet',['t_start','t_stop']) ;  
  movedata('messages',['created']) ;  
  movedata('eventlist',['eventdate']) ;  
  movedata('custitems',['updatedon']) ;  
  movedata('equipments') ;  
  movedata('iostate') ;  
  movedata('item_set') ;  
  movedata('parealist') ;  
  movedata('poslist') ;  
  movedata('subscribes') ;  
  movedata('taglist') ;  
  movedata('promocodes') ;  
  movedata('notifies',['dateshow']) ;  
  movedata('stats',['dt']) ;  
  movedata('crontask') ;  
  movedata('keyval') ;  
  movedata('issue_projectlist') ;  
  movedata('issue_issuelist',['lastupdate']) ;  
  movedata('issue_time',['createdon']) ;  
  movedata('issue_projectacc') ;  
  movedata('issue_history') ;  
  movedata('note_nodes') ;  
  movedata('note_topics') ;  
  movedata('note_topicnode') ;  
  movedata('note_tags') ;  
  movedata('note_fav') ;  
  movedata('ppo_zformstat',['createdon']) ;  
  movedata('ppo_zformrep',['createdon']) ;  
  movedata('prodproc') ;  
  movedata('prodstage') ;  
  movedata('prodstageagenda',['startdate','enddate']) ;  
  movedata('shop_attributes') ;  
  movedata('shop_attributes_order') ;  
  movedata('shop_attributevalues') ;  
  movedata('shop_prod_comments',['created']) ;  
  movedata('shop_prod_comments') ;  
  movedata('files') ;  
  movedata('filesdata') ;  
  movedata('images') ;  
   
 
    
echo "OK";