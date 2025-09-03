<table class="ctable" border="0" cellspacing="0" cellpadding="2">


    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="7" valign="middle">
            <br><br> Повернення № {{document_number}} від {{date}} <br><br><br>
        </td>
    </tr>
    {{#fiscalnumber}}    
    <tr>
        <td></td>
        <td>Фіскальний номер</td>
        <td colspan="5">{{fiscalnumber}}</td>
    </tr>
    {{/fiscalnumber}}    
   <tr>
        <td></td>
        <td>Покупець</td>
        <td colspan="5">{{customer_name}}</td>
    </tr>
   <tr>
        <td colspan="7">{{{notes}}}</td>
    </tr>
    
    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Найменування
        </th>

        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Кіл.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Ціна  {{#nds}} (без ПДВ)  {{/nds}}</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td colspan="2">{{tovar_name}}</td>

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}  {{#nds}} ({{pricenonds}})  {{/nds}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="5" align="right">Разом:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>
       {{#nds}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="5" align="right">В т.ч. ПДВ:</td>
        <td style="border-top:1px #000 solid;" align="right">{{nds}}</td>
    </tr>
       {{/nds}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="5" align="right">До сплати:</td>
        <td style="border-top:1px #000 solid;" align="right">{{payamount}}</td>
    </tr>
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="5" align="right">Оплачено:</td>
        <td style="border-top:1px #000 solid;" align="right">{{payed}}</td>
    </tr>


</table>

