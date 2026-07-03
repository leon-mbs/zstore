<font bold="true">a</font>
 
<text>Рахунок {{document_number}}</text>
  
    
<font >a</font>
<text>вiд {{date}}</text>
<align>left</align>

 

<text>{{firm_name}}</text>   
   
 
<text>{{address}}</text>    
 
    {{#customer_name}}
<text>Покупець: {{customer_name}}</text>    

    {{/customer_name}}
    
 
 

<separator>-</separator>
    {{#_detail}}
<text>{{tovar_name}}</text>     
 
 <row>
  <col align="right" length="22" >{{quantity}}</col>
  <col align="right" length="10" >{{amount}}</col>
</row>
 
    {{/_detail}}
<separator>-</separator>
<font bold="true">a</font>

<align>right</align>
<text>Всього: {{total}}</text>
<font >a</font>
 

 
    {{#isdisc}}
<text>Знижка: {{totaldisc}}</text>
 
    {{/isdisc}}
   {{#bonus}}
 <text>Списано бонусiв: {{bonus}}</text>
 
    {{/bonus}}

 <text>До сплати: {{payamount}}</text>
 
    
 
<newline ></newline>
 