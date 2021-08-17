<table class="ctable" border="0" cellspacing="0" cellpadding="2"  {{{style}}}} >


    <tr>
        <td></td>
        <td valign="top"><b>Покупатель</b></td>
        <td colspan="5">{{customer_name}}</td>
    </tr>
    {{#isfirm}}
    <tr>

        <td></td>
        <td valign="top"><b>Продавец</b></td>
        <td colspan="5">{{firm_name}}</td>

    </tr>
    {{/isfirm}}


    <tr>
        <td></td>
        <td valign="top"><b>Склад</b></td>
        <td colspan="5">{{store_name}}</td>
    </tr>

    {{#order}}
    <tr>
        <td></td>
        <td><b>Заказ</b></td>
        <td colspan="5">{{order}}</td>
    </tr>
    {{/order}}
    <tr>
        <td></td>
        <td><b>Телефон</b></td>
        <td colspan="5">{{phone}}</td>
    </tr>
    <tr>
        <td></td>
        <td><b>Email</b></td>
        <td colspan="5">{{email}}</td>
    </tr>

    <tr>
        <td></td>
        <td><b>Доставка</b></td>
        <td>{{delivery_name}}</td>
        <td colspan="4">{{ship_address}}</td>
    </tr>


    {{#ship_number}}
    <tr>
        <td></td>
        <td><b>№ декларации</b></td>
        <td colspan="4">{{ship_number}}</td>
    </tr>
    {{/ship_number}}
    <tr>
        <td></td>
        <td><b>Дата отправки</b></td>
        <td colspan="5">{{sent_date}}</td>
    </tr>
    <tr>
        <td></td>
        <td><b>Дата доставки</b></td>
        <td colspan="5">{{delivery_date}}</td>
    </tr>
    <tr>
        <td></td>
        <td><b>Ответственный</b></td>
        <td colspan="5"> {{emp_name}}</td>
    </tr>
    {{#ship_amount}}
    <tr>
        <td></td>
        <td><b>Стоимость доставки</b></td>
        <td colspan="5"> {{ship_amount}}</td>
    </tr>
    {{/ship_amount}}

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="7" valign="middle">
            ТТН № {{document_number}} от {{date}} <br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Наименование</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Ед.</th>

        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Кол.</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Цена</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сумма</th>
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
        <td style="border-top:1px #000 solid;" colspan="2">{{weight}}</td>
        <td style="border-top:1px #000 solid;" colspan="4" align="right">Итого:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>


</table>

