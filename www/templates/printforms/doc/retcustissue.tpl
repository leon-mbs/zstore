<table class="ctable" border="0" cellspacing="0" cellpadding="2">


    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="6" valign="middle">
            Повернення постачальнику № {{document_number}} від {{date}} <br>
        </td>
    </tr>
    <tr>
        <td></td>
        <td><b>Постачальник</b></td>
        <td colspan="4">{{customer_name}}</td>
    </tr>
   <tr>
        <td colspan="6">{{{notes}}}</td>
    </tr>
    
    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Найменування</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>


        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Кіл.</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Ціна</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td>{{tovar_name}}</td>
        <td>{{tovar_code}}</td>


        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="5" align="right">Разом:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="5" align="right">Оплачено:</td>
        <td style="border-top:1px #000 solid;" align="right">{{payed}}</td>
    </tr>


</table>

