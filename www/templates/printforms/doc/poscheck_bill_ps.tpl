<font bold="true">a</font>
<text>Чек {{document_number}}</text>
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
    
    
    
    
        {{#form1}}
 <text>Форма оплати: готiвка</text>        
        {{/form1}}
        {{#form2}}
 <text>Форма оплати: безготiвка</text>        
        {{/form2}}    
        {{#form3}}
 <text>Форма оплати: iнше</text>        
        {{/form3}}    
        {{#payeq}}
 <text>Засiб оплати:  </text>        
 <text>{{payeq}}  </text>        
          
        {{/payeq}}     
        {{#trans}}
 <text>№ транзакції  {{trans}}</text>        
          
        {{/trans}}      
    
<text>Термінал: {{pos_name}}</text>    
<text>Касир: {{username}}</text>    

 

<separator>-</separator>
    {{#_detail}}
<text>{{tovar_name}}</text>     
 
 <row>
  <col align="right" length="14" >{{quantity}}</col>
  <col align="right" length="8" >{{price}}</col>
  <col align="right" length="10" >{{amount}}</col>
</row>
 
    {{/_detail}}
<separator>-</separator>
<font bold="true">a</font>

<align>right</align>
<text>Всього: {{total}}</text>
<font >a</font>
 

  
    {{#totaldisc}}
<text>Знижка: {{totaldisc}}</text>
 
    {{/totaldisc}}
   {{#delbonus}}
 <text>Списано бонусiв: {{delbonus}}</text>
 
    {{/delbonus}}
   {{#prepaid}}
 <text>Передплата: {{prepaid}}</text>
 
    {{/prepaid}}

 <text>До сплати: {{payamount}}</text>
 <text>Оплата: {{payed}}</text>
 {{#exchange}}
 <text>Решта: {{exchange}}</text>
 {{/exchange}} 
 

 
        
          
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

{{#isdocqrcode}}
<font >a</font>
<newline ></newline>
<qrcode>{{docqrcodeurl}}</qrcode>
{{/isdocqrcode}}
<newline ></newline>
 
   