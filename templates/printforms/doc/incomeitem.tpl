<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="6" align="center">
            <b> Оприбуткування ТМЦ № {{document_number}} від {{date}}</b> <br>
        </td>
    </tr>

    <tr>
        <td colspan="6">
            <b>На склад:</b> {{to}}
        </td>
    </tr>
    {{#emp}}
    <tr>
        <td colspan="6">
            <b>Спiвробiтник:</b> {{emp}}
        </td>
    </tr>
    <tr>
        <td colspan="6">
            <b>Сума:</b> {{examount}}
        </td>
    </tr>
    {{/emp}}

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Найменування</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"></th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>


        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Кіл.</th>
        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Ціна</th>
        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Сума</th>

    </tr>
    {{#_detail}}
    <tr>

        <td>{{item_name}}</td>
        <td>{{item_code}}</td>

        <td align="right">{{snumber}}</td>
        <td>{{msr}}</td>
        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="6" align="right">На суму:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>
    <tr>
        <td colspan="6">
            {{{notes}}}
        </td>

    </tr>
</table>



