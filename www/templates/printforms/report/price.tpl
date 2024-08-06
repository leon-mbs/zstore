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
        {{#showimage}}
        <th> </th>
        {{/showimage}}
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
 <th>Примітка</th>
    </tr>
    {{#_detail}}
    <tr>
        {{#showimage}}
        <td style="border-top:1px #ccc solid;" >
          {{#isimage}}
          <img src="{{im}}"  style="width:64px" />
           {{/isimage}}
        </td>
        {{/showimage}}

        <td style="border-top:1px #ccc solid;">{{name}}</td>
        <td style="border-top:1px #ccc solid;">{{code}}</td>
        <td style="border-top:1px #ccc solid;">{{msr}}</td>

  {{#iscat}}        <td style="border-top:1px #ccc solid;">{{cat}}</td>     {{/iscat}}  
  {{#isbrand}}      <td style="border-top:1px #ccc solid;">{{brand}}</td>   {{/isbrand}}  
{{#showqty}}        <td style="border-top:1px #ccc solid;" align="right">{{qty}}</td> {{/showqty}}
{{#price1name}}         <td style="border-top:1px #ccc solid;" align="right">{{price1}}</td> {{/price1name}} 
{{#price2name}}         <td style="border-top:1px #ccc solid;" align="right">{{price2}}</td> {{/price2name}} 
{{#price3name}}         <td style="border-top:1px #ccc solid;" align="right">{{price3}}</td> {{/price3name}} 
{{#price4name}}         <td style="border-top:1px #ccc solid;" align="right">{{price4}}</td> {{/price4name}} 
{{#price5name}}         <td style="border-top:1px #ccc solid;" align="right">{{price5}}</td> {{/price5name}} 
{{#showdesc}}           <td style="border-top:1px #ccc solid;">{{desc}}</td>{{/showdesc}}
        <td style="border-top:1px #ccc solid;">{{notes}}</td>
    </tr>
    {{/_detail}}
</table>
<br>

