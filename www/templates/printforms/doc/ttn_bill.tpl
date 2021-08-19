<table class="ctable" border="0" cellpadding="1" cellspacing="0"  {{{style}}}}>
    <tr>
        <td colspan="3">ТТН №{{document_number}}</td>
    </tr>
    <tr>

        <td colspan="3">от {{date}}</td>
    </tr>
    <tr>
        <td colspan="3"> Продавец:</td>
    </tr>
    <tr>

        <td colspan="2"> {{firm_name}}</td>
    </tr>


    <tr>
        <td colspan="3"> Тел. {{phone}}</td>
    </tr>
    {{#customer_name}}
    <tr>
        <td colspan="3"> Покупатель:</td>
    </tr>
    <tr>
        <td colspan="3"> {{customer_name}}</td>
    </tr>

    {{/customer_name}}
    {{#order}}
    <tr>
        <td colspan="3"> Заказ:</td>
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
        <td colspan="2" align="right">Всего:</td>
        <td align="right">{{total}}</td>
    </tr>


    <tr>
        <td colspan="3"> Дата отправки</td>
    </tr>
    <tr>

        <td colspan="2"> {{sent_date}}</td>
    </tr>
    {{#ship_number}}
    <tr>
        <td colspan="3"> № декларации</td>
    </tr>
    <tr>

        <td colspan="2"> {{ship_number}}</td>
    </tr>
</tr>
{{/ship_number}}
</table>