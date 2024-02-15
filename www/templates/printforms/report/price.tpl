<table class="ctable" border="0"   cellpadding="2" cellspacing="0">


    <tr>

        <td style="font-size:larger" align="center" colspan="8">
          <b>  Прайс від {{date}}</b><br><br>
        </td>
    </tr>
 {{^iscat}}   
    <tr>
        <td  colspan="11">
        <b>  Категорія:</b> {{catname}} <br>
        </td>
    </tr>
 {{/iscat}}  
 {{^isbrand}}      
    <tr>
        <td  colspan="8">
        <b>  Бренд:</b> {{brandname}}  <br>
        </td>
    </tr>
 {{/isbrand}}  
    <tr style="font-weight: bolder;">

        <th>Найменування</th>
        <th>Код</th>
        <th>Од. вим.</th>
     {{#iscat}}   <th>Категорія</th>  {{/iscat}}
     {{#isbrand}}     <th>Бренд</th> {{/isbrand}} 
{{#showqty}}      <th align="right">Кiл.</th>{{/showqty}}
        
{{#price1name}}          <th align="right">{{price1name}}</th>  {{/price1name}} 
{{#price2name}}          <th align="right">{{price2name}}</th>  {{/price2name}} 
{{#price3name}}          <th align="right">{{price3name}}</th>  {{/price3name}} 
{{#price4name}}          <th align="right">{{price4name}}</th>  {{/price4name}} 
{{#price5name}}          <th align="right">{{price5name}}</th>  {{/price5name}} 
{{#showdesc}}      <th align="right">Опис</th>{{/showdesc}}
    </tr>
    {{#_detail}}
    <tr>

        <td>{{name}}</td>
        <td>{{code}}</td>
        <td>{{msr}}</td>
  {{#iscat}}        <td>{{cat}}</td>     {{/iscat}}  
  {{#isbrand}}      <td>{{brand}}</td>   {{/isbrand}}  
{{#showqty}}        <td align="right">{{qty}}</td> {{/showqty}}
{{#price1name}}         <td align="right">{{price1}}</td> {{/price1name}} 
{{#price2name}}         <td align="right">{{price2}}</td> {{/price2name}} 
{{#price3name}}         <td align="right">{{price3}}</td> {{/price3name}} 
{{#price4name}}         <td align="right">{{price4}}</td> {{/price4name}} 
{{#price5name}}         <td align="right">{{price5}}</td> {{/price5name}} 
{{#showdesc}}           <td>{{desc}}</td>{{/showdesc}}

    </tr>
    {{/_detail}}
</table>
<br>

