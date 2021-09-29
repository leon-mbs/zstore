<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="2" align="center">
            <b> Виплата зарплати № {{document_number}} від {{date}}</b> <br>
        </td>
    </tr>


    <tr>
        <td colspan="2">
            <b>З рахунку:</b> {{paymentname}}
        </td>
    </tr>

    <tr>
        <td colspan="2">
            <b>Мiсяць:</b> {{month}} {{year}}
        </td>
    </tr>
     {{#advance}}
  <tr>
        <td colspan="2">
            <b>Виплата авансу</b> 
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
            <b>ПIБ</b>
        </td>
        <td class="text-right">
            <b>Сума</b>
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
            <b>Всього:</b>
        </td>
        <td class="text-right">
            <b>{{total}}</b>
        </td>
    </tr>

</table>


