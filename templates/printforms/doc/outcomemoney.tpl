<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="4" align="center">
            <b> Видатковий ордер № {{document_number}} від {{date}}</b> <br>
        </td>
    </tr>


    <tr>
        <td colspan="4">
            <b>З рахунку:</b> {{from}}
        </td>
    </tr>
    <tr>
        <td colspan="4">
            <b>Сума:</b> {{amount}}
        </td>
    </tr>
    <tr>
        <td colspan="4">
            <b>Тип витрати:</b> {{type}}
        </td>
    </tr>
    {{#customer}}
    <tr>
        <td colspan="4">
            <b>Контрагент:</b> {{customer}}
        </td>
    </tr>
    {{/customer}}
    {{#contract}}
    <tr>
        <td colspan="4">
            <b>Договір:</b> {{contract}}
        </td>
    </tr>
    {{/contract}}
    {{#emp}}
    <tr>
        <td colspan="4">
            <b>Спiвробiтник:</b> {{emp}}
        </td>
    </tr>
    {{/emp}}
    <tr>
        <td colspan="4">
            {{{notes}}}
        </td>
    </tr>


</table>


