<table class="ctable" border="0" cellspacing="0" cellpadding="2">


    <tr>
        <td></td>
        <td valign="top"><b>Покупець</b></td>
        <td colspan="5">{{customer_name}}</td>
    </tr>

    {{#order}}
    <tr>
        <td></td>
        <td><b>Замовлення</b></td>
        <td colspan="5">{{order}}</td>
    </tr>
    {{/order}}
    {{#isdelivery}}
    <tr>
        <td></td>
        <td><b>Адреса</b></td>
        <td colspan="5">{{ship_address}}</td>
    </tr>


    <tr>
        <td></td>
        <td><b>Декларація</b></td>
        <td colspan="4">{{ship_number}} </td>
    </tr>
    <tr>
        <td></td>
        <td><b>Дата відправки</b></td>
        <td colspan="5">{{sent_date}} </td>
    </tr>
    <tr>
        <td></td>
        <td><b>Дата доставки</b></td>
        <td colspan="5">{{delivery_date}} </td>
    </tr>
    <tr>
        <td></td>
        <td><b>Відповідальний</b></td>
        <td colspan="5"> {{emp_name}}</td>
    </tr>
    {{/isdelivery}}
    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="7" valign="middle">
            Накладна № {{document_number}} від {{date}} <br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Найменування</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>

        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Кіл.</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Ціна</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td>{{tovar_name}}</td>
        <td>{{tovar_code}}</td>
        <td>{{msr}}</td>

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="2">{{weight}} </td>

        <td style="border-top:1px #000 solid;" colspan="4" align="right">Разом:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>

    {{^prepaid}}
    {{#isdisc}}
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">Знижка:</td>
        <td align="right">{{paydisc}}</td>
    </tr>
    {{/isdisc}}
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">До оплати:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
    {{/prepaid}}
    <tr>
        <td colspan="7">На суму <b>{{totalstr}}<b></td>

    </tr>

</table>

