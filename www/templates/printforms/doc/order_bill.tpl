<table class="ctable"  border="0" cellpadding="1" cellspacing="0" {{{style}}}>
    <tr>
        <td colspan="3">Заказ {{document_number}}</td>
    </tr>
    <tr>

        <td colspan="3">от {{date}}</td>
    </tr>
    <tr>
        <td colspan="3"> Продавец:</td>
    </tr>
    <tr>

        <td colspan="3"> {{firm_name}}</td>
    </tr>


    <tr>
        <td colspan="3"> {{phone}}</td>
    </tr>

    <tr>
        <td colspan="3"> Покупатель:</td>
    </tr>
    <tr>
        <td colspan="3"> {{customer_name}}</td>
    </tr>


    <tr>
        <td colspan="3">Доставка {{delivery}}</td>

    </tr>
    <tr>
        <td colspan="3">{{ship_address}}</td>
    </tr>

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


</table>