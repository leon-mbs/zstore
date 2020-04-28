<table class="ctable" border="0" cellspacing="0" cellpadding="2">


    <tr>
        <td></td>
        <td>Постачальник</td>
        <td colspan="6">{{customer_name}}</td>
    </tr>
    <tr>
        <td></td>
        <td>Підстава</td>
        <td colspan="6">{{basedoc}}</td>
    </tr>

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="6" valign="middle">
            <br> Накладна № {{document_number}} від {{date}} <br><br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Найменування</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"></th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>

        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="50">Кол.</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Ціна</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td>{{itemname}}</td>
        <td>{{itemcode}}</td>
        <td align="right">{{snumber}}</td>
        <td>{{msr}}</td>

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="7" align="right">Разом:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>
    {{^prepaid}}
    {{#isdisc}}
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">Знижка:</td>
        <td align="right">{{disc}}</td>
    </tr>
    {{/isdisc}}
    {{#isnds}}
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">ПДВ:</td>
        <td align="right">{{nds}}</td>
    </tr>
    {{/isnds}}
    {{#israte}}
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">Курс:</td>
        <td align="right">{{rate}}</td>
    </tr>
    {{/israte}}

    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">До оплати:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
    {{/prepaid}}

</table>

