<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="6" align="center">
            <b> Оприходование ТМЦ № {{document_number}} от {{date}}</b> <br>
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
            <b>Сотрудник:</b> {{emp}}
        </td>
    </tr>
    <tr>
        <td colspan="6">
            <b>Сумма:</b> {{examount}}
        </td>
    </tr>
    {{/emp}}

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Название</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"></th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Ед.</th>


        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Кол.</th>
        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Цена</th>
        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Сумма</th>

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
        <td style="border-top:1px #000 solid;" colspan="6" align="right">На сумму:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>
    <tr>
        <td colspan="6">
            {{{notes}}}
        </td>

    </tr>
</table>



