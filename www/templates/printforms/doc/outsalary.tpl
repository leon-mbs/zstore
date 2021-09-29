<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="2" align="center">
            <b> Выплата зарплаты № {{document_number}} от {{date}}</b> <br>
        </td>
    </tr>


    <tr>
        <td colspan="2">
            <b>Со счета:</b> {{paymentname}}
        </td>
    </tr>

    <tr>
        <td colspan="2">
            <b>Месяц:</b> {{month}} {{year}}
        </td>
    </tr>
    {{#advance}}
  <tr>
        <td colspan="2">
            <b>Выплата аванса</b> 
        </td>
    </tr>
    {{/advance}}
    <tr>
        <td colspan="2">
            {{{notes}}}
        </td>
    </tr>
    <tr>
        <td>
            <b>ФИО</b>
        </td>
        <td class="text-right">
            <b>Сумма</b>
        </td>
    </tr>

    {{#_detail}}
    <tr>
        <td>
            {{emp_name}}
        </td>
        <td class="text-right">
            {{amount}}
        </td>
    </tr>

    {{/_detail}}
    <tr>
        <td>
            <b>Всего:</b>
        </td>
        <td class="text-right">
            <b>{{total}}</b>
        </td>
    </tr>

</table>


