<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="4" align="center">
            <b> Видатковий ордер № {{document_number}} від {{date}}</b> <br>
        </td>
    </tr>


    <tr>
        <td><b>З рахунку:</b></td><td colspan="3">{{from}}</td>
    </tr>
    <tr>
        <td><b>Сума:</b></td><td colspan="3">{{amount}}</td>
    </tr>
    <tr>
        <td colspan="4">
            {{totalstr}}   
        </td>
    </tr>
  
    {{#customer}}
    <tr>
        <td><b>Контрагент:</b></td><td colspan="3">{{customer}}</td>
    </tr>
    {{/customer}}
    {{#contract}}
    <tr>
        <td><b>Договір:</b></td><td colspan="3">{{contract}}</td>
    </tr>
    {{/contract}}
    {{#emp}}
    <tr>
        <td><b>Співробітник:</b></td><td colspan="3">{{emp}}</td>
    </tr>
    {{/emp}}
    <tr>
        <td colspan="4">
            {{{notes}}}
        </td>
    </tr>


</table>


