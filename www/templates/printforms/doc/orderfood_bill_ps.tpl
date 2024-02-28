<font bold="true">a</font>
    {{#ischeck}}
<text>Чек {{document_number}}</text>
    {{/ischeck}}
    {{^ischeck}}
<text>Рахунок {{document_number}}</text>
    {{/ischeck}}
    
<font >a</font>
<text>вiд {{date}}</text>
<align>left</align>

    {{#fiscalnumber}}
<text>Фiскальний чек</text>
<text>ФН чека {{fiscalnumber}}</text>
    {{/fiscalnumber}}
   {{#fiscalnumberpos}}
<text>ФН РРО {{fiscalnumberpos}}</text>   
    {{/fiscalnumberpos}}

<text>{{firm_name}}</text>   
{{#inn}}  
<text>ІПН {{inn}}</text>   
{{/inn}}  
{{#tin}}  
<text>ЄДРПОУ {{tin}}</text>   
{{/tin}}  
<text>{{shopname}}</text>   
  {{#shopname}}
<text>{{shopname}}</text>   
  {{/shopname}}
<text>{{address}}</text>    
 
    {{#customer_name}}
<text>Покупець: {{customer_name}}</text>    

    {{/customer_name}}
<text>Термінал: {{pos_name}}</text>    
<text>Касир: {{username}}</text>    

 

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
 

    {{^prepaid}}
    {{#isdisc}}
<text>Знижка: {{totaldisc}}</text>
 
    {{/isdisc}}
   {{#bonus}}
 <text>Списано бонусiв: {{bonus}}</text>
 
    {{/bonus}}

 <text>До сплати: {{payamount}}</text>
 
    {{#ischeck}} 
 
 <text>Оплата: {{payed}}</text>
 {{#exchange}}
 <text>Решта: {{exchange}}</text>
 {{/exchange}} 
 
    {{/ischeck}} 
     
    {{/prepaid}}
    {{#addbonus}}
 <text>Нараховано бонусiв: {{addbonus}}</text>
 
    {{/addbonus}}
    {{#allbonus}}

 <text>Всього бонусiв: {{allbonus}}</text>
 
    {{/allbonus}}
    

<font bold="true">a</font>
<align>center</align>
<newline ></newline>

{{#checkslogan}}
<text>  {{checkslogan}}</text>
{{/checkslogan}}

{{#promo}}
<text>  {{promo}}</text>  
{{/promo}}  

    {{#ischeck}} 

{{#docqrcodeurl}}
<font >a</font>
<newline ></newline>
<qrcode type="code128"> {{docqrcodeurl}}</qrcode>
{{/docqrcodeurl}}

    {{/ischeck}} 
<newline ></newline>
 