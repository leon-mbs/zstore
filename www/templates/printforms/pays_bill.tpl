<table class="ctable" border="0" cellpadding="1" cellspacing="0" {{{printw}}}>
    <tr>
        <td colspan="2"> <b>Квитанцiя про  оплату </b></td>
    </tr>
    <tr>

        <td colspan="2">Пiдстава {{document_number}}</td>
    </tr>
    <tr>

        <td colspan="2"> {{firm_name}}</td>
    </tr>



    <tr>
        <td colspan="2"> {{customer_name}}</td>
    </tr>


    <tr>
        <td colspan="2">Оплати:</td>
    </tr>
    {{#plist}}
    <tr>
        <td>{{pdate}}</td>
        <td align="right">{{ppay}}</td>
    </tr>

    {{/plist}}

    <tr>
        <td  align="right"> <b> Всього:</b></td>
        <td  align="right"><b> {{pall}}</b></td>

    </tr>

</table>
<br>