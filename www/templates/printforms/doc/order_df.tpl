<table class="ctable" border="0" cellspacing="0" cellpadding="2">


  

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="10" valign="middle">
            Замовлення № {{document_number}} від {{date}}
        </td>
    </tr>
     {{#isoutnumber}}
    <tr>
        <td></td>
        <td>Зовнiшнiй номер</td>
        <td colspan="8">{{outnumber}}</td>
    </tr>

    {{/isoutnumber}}

    <tr style="font-weight: bolder;">
        
         <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Найменування        </th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Од.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;"> </th>

        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Кіл.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Склад. ціна</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Прод. ціна</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Сума</th>
    </tr>
    {{#_detail}}
    <tr>
     
        <td colspan="2">{{{tovar_name}}}</td>
        <td colspan="2" valign="top">{{tovar_code}}</td>
        <td valign="top">{{msr}}</td>
        <td valign="top">{{desc}}</td>

        <td align="right" valign="top">{{quantity}}</td>
        <td align="right" valign="top">{{pricefrom}}</td>
        <td align="right" valign="top">{{price}}</td>
        <td align="right" valign="top">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="7" align="right">На суму:</td>
        <td style="border-top:1px #000 solid;" align="right">{{totalfrom}}</td>
        <td style="border-top:1px #000 solid;" align="right"> </td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>
  
   <tr>
        <td></td>
        <td> Доставка</td>
        <td>{{delivery}}</td>
        <td colspan="7">Адреса: {{ship_address}}</td>
    </tr>
    <tr>
        <td colspan="10">{{{notes}}}</td>
    </tr>

 


 
 

    
</table>

