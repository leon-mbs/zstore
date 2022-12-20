<align>center</align>
<font bold="true">a</font>
<text>Замовлення {{document_number}}</text>
<font >a</font>

<text>вiд {{date}}</text>
<align>left</align>
<text>Продавець: {{firm_name}}</text>
<text>Тел. {{phone}}</text>
<text>Покупець: {{customer_name}}</text>
<text>Доставка {{delivery}}</text>
<text>{{ship_address}}</text>
 <newline> </newline>
 {{#_detail}}
 <text>{{tovar_name}}</text>
<row>
  <col align="right" length="12" >{{quantity}}</col>
  <col align="right" length="10" >{{price}}</col>
  <col align="right" length="10" >{{amount}}</col>
</row>
{{/_detail}}
<font bold="true">a</font>
<align>right</align>
<text>Всього: {{total}}</text>
<newline ></newline>
 
 
 