<table class="ctable" border="0" cellpadding="1" cellspacing="0" {{{printw}}}>
    <tr>
        <td colspan="2"><b>Квитанция об оплате </b></td>
    </tr>
    <tr>

        <td colspan="2">Основание {{document_number}}</td>
    </tr>
    <tr>

        <td colspan="2"> {{firm_name}}</td>
    </tr>



    <tr>
        <td colspan="2"> {{customer_name}}</td>
    </tr>


    <tr>
        <td colspan="2"><b>Оплаты:</b></td>
    </tr>
    {{#plist}}
    <tr>
        <td>{{pdate}}</td>
        <td align="right">{{ppay}}</td>
    </tr>

    {{/plist}}

    <tr>
        <td  align="right"> <b> Всего:</b></td>
        <td  align="right"><b> {{pall}}</b></td>

    </tr>

</table><br>