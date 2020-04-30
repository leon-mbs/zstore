<table class="ctable" border="0" cellspacing="0" cellpadding="2">


    <tr>
        <td></td>
        <td>Заказчик</td>
        <td colspan="7">{{customer_name}}</td>
    </tr>
    <tr>
        <td></td>
        <td>Телефон</td>
        <td colspan="7">{{phone}}</td>
    </tr>
    <tr>
        <td></td>
        <td>Email</td>
        <td colspan="7">{{email}}</td>
    </tr>

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="9" valign="middle">
            Счет-фактура № {{document_number}} от {{date}}
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Наименование
        </th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Ед.</th>

        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Кол.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Цена</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Сумма</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td colspan="2">{{tovar_name}}</td>
        <td colspan="2">{{tovar_code}}</td>
        <td>{{msr}}</td>

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="8" align="right">Итого:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>
    {{#paydisc}}
    <tr style="font-weight: bolder;">
        <td colspan="8" align="right">Скидка:</td>
        <td align="right">{{paydisc}}</td>
    </tr>
    {{/paydisc}}
    <tr style="font-weight: bolder;">
        <td colspan="8" align="right">К оплате:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    <tr style="font-weight: bolder;">
        <td colspan="8" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
    <tr>
        <td colspan="7">На сумму <b>{{totalstr}}<b></td>

    </tr>
</table>

