<table class="ctable" border="0" cellpadding="2" cellspacing="0">
    <tr>
        <td colspan="5">
            Замовник: {{customer_name}}
        </td>


    </tr>
    {{#isfirm}}
    <tr>
        <td colspan="5">
            Виконавець: {{firm_name}}
        </td>

    </tr>
    {{/isfirm}}
    {{#iscontract}}
    <tr>
        <td colspan="5">
            Угода: {{contract}} вiд {{createdon}}
        </td>

    </tr>
    {{/iscontract}}

    {{#isdevice}}
    <tr>
        <td colspan="5">
            Виріб, матеріали: {{device}} с/н: {{devsn}}
        </td>

    </tr>
    {{/isdevice}}
    <tr style="font-weight: bolder;">
        <td colspan="5" align="center">
           
          {{#isfinished}}  Акт виконаних робіт {{/isfinished}} 
          {{^isfinished}}  Квитанція до {{/isfinished}} 
           № {{document_number}} від {{date}}
        </td>
    </tr>

</table>
<br>
<table class="ctable" width="600" cellspacing="0" cellpadding="1" border="0">
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;">Найменування</th>
        <th style="border: 1px solid black;"> </th>
        <th style="border: 1px solid black;" align="right">Вартість</th>
        <th style="border: 1px solid black;" align="right">Сума</th>

    </tr>
    {{#_detail}}
    <tr>
        <td>{{no}}</td>
        <td>{{service_name}}</td>

        <td>{{desc}}</td>

        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td colspan="4" style="border-top: 1px solid black;" align="right">Всього:</td>
        <td style="border-top: 1px solid black;" align="right">{{total}}</td>
    </tr>
    {{#isdisc}}
    <tr style="font-weight: bolder;">
        <td colspan="4" align="right">Знижка:</td>
        <td align="right">{{paydisc}}</td>
    </tr>
    {{/isdisc}}    
   {{#payamount}}
    <tr style="font-weight: bolder;">
        <td colspan="4" align="right">До оплати:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    {{/payamount}} 
   {{#payed}}  
    <tr style="font-weight: bolder;">
        <td colspan="4" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
     {{/payed}}  
    <tr>
        <td colspan="5"><br>
            Гарантія: {{gar}}
        </td>
    </tr>
    <tr>
        <td colspan="5">{{{notes}}}</td>
    </tr>

</table>


