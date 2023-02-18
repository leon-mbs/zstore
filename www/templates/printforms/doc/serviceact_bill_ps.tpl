<align>center</align>
<font bold="true">a</font>
<text>
   {{#isfinished}}  Акт виконаних робіт {{/isfinished}} 
          {{^isfinished}}  Квитанція до {{/isfinished}} 
         {{document_number}}
</text>
<font >a</font>
<text>вiд {{date}}</text>
<align>left</align>
<text> {{firmname}}</text>
    {{#shopname}}
<text> {{shopname}}</text>
    {{/shopname}}
<text> {{address}}</text>    
<text>Тел. {{phone}}</text>    
<text> {{customer_name}}</text>    

    {{#isdevice}}
<text>Прийнято від клієнта:</text>       
<text>{{device}}</text>       
<text>с/н {{serial}}</text>       
    {{/isdevice}}
    {{#iswork}}
<text>Роботи:</text>       
    {{#slist}}

<row>
  <col length="22" >{{service_name}}</col>
  <col align="right" length="10" >{{amount}}</col>
</row>
    {{/slist}}

   {{/iswork}}  
  
    {{#isitems}}
<text>Комплектуючі:</text>       
 
    {{#ilist}}
 
<row>
  <col length="22" >{{itemname}}</col>
  <col align="right" length="10" >{{amount}}</col>
</row>
    
    {{/ilist}}
    {{/isitems}}
   
    {{#istotal}}
<align>right</align>
<text>Всього: {{total}}</text>
    {{/istotal}}


    {{#ispay}}
<align>left</align> 
<text>Оплати:</text>  
 
    {{#plist}}
    
<row>
  <col length="22" >{{pdate}}</col>
  <col align="right" length="10" >{{ppay}}</col>
</row>    
    
 

    {{/plist}}
    {{/ispay}}
   
<text>{{gar}}</text>      
 <newline ></newline>   
 <text>Виконавець ________</text>  
 <newline ></newline>  
 <text>Клієнт ________</text>  
 <newline ></newline>  
 