<align>center</align>
<font bold="true">a</font>
<text>Накладна {{document_number}}</text>
<font >a</font>
<text>вiд {{date}}</text>
<align>left</align>
<text>Продавець: {{firm_name}}</text>

    {{#isbank}}
<text>{{bank}}</text>
<text>р/р {{bankacc}}</text>    

<text>Тел. {{phone}}</text>
<text>Покупець: {{customer_name}}</text>
<separator>-</separator>
 {{#_detail}}
 <text>{{tovar_name}}</text>
<row>
  <col align="right" length="12" >{{quantity}}</col>
  <col align="right" length="10" >{{price}}</col>
  <col align="right" length="10" >{{amount}}</col>
</row>
{{/_detail}}
<font bold="true">a</font>
<separator>-</separator>
<align>right</align>
<text>Всього: {{total}}</text>
<newline ></newline>
