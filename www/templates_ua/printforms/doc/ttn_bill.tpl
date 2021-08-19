<table class="ctable" border="0" cellpadding="1" cellspacing="0"  {{{style}}}}>
    <tr>
        <td colspan="3">ТТН №{{document_number}}</td>
    </tr>
    <tr>

        <td colspan="3">от {{date}}</td>
    </tr>
    <tr>
        <td colspan="3"> Продавець:</td>
    </tr>
    <tr>

        <td colspan="2"> {{firm_name}}</td>
    </tr>


    <tr>
        <td colspan="3"> Тел. {{phone}}</td>
    </tr>
    {{#customer_name}}
    <tr>
        <td colspan="3"> Покупець</td>
    </tr>
    <tr>
        <td colspan="3"> {{customer_name}}</td>
    </tr>

    {{/customer_name}}
    {{#order}}
    <tr>
        <td colspan="3"> Замовлення</td>
    </tr>
    <tr>

        <td colspan="2"> {{order}}</td>
    </tr>


    {{/order}}

    {{#_detail}}
    <tr>
        <td colspan="3">{{tovar_name}}</td>

    </tr>


    <tr>

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr>
        <td colspan="2" align="right">Всього:</td>
        <td align="right">{{total}}</td>
    </tr>


    <tr>
        <td colspan="3"> Дата відправки</td>
    </tr>
    <tr>

        <td colspan="2"> {{sent_date}}</td>
    </tr>
    {{#ship_number}}
    <tr>
        <td colspan="3"> № декларацii</td>
    </tr>
    <tr>

        <td colspan="2"> {{ship_number}}</td>
    </tr>
</tr>
{{/ship_number}}
</table>