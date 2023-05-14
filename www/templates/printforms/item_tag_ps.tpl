<align>left</align>
<font >a</font>
<font bold="true">b</font>
<text>{{name}}</text>
 {{#isarticle}}
<font bold="false">a</font> 
<text>Код {{article}}</text>
 {{/isarticle}}
 {{#isprice}}
 
<align>right</align>
<font bold="true">b</font>
<size height="2" width="2" ></size>
<text  >  {{price}}</text>
 {{/isprice}}
 
<align>center</align>
{{#isbarcode}}
  <barcode type="code128" >{{barcode}}</barcode>
{{/isbarcode}}
{{#isqrcode}}
  <qrcode>{{qrcode}}</qrcode>
{{/isqrcode}}
<newline></newline>

