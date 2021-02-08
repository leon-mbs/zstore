<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="4" align="center">
            <b> Договор № {{document_number}} от {{date}}</b> <br>
        </td>
    </tr>

 
 
    <tr>
        <td colspan="4">
            <b>Компания:</b> {{comp}}
        </td>
    </tr>
   <tr>
        <td colspan="4">
            <b>Контрагент:</b> {{customer}}
        </td>
    </tr>
   
    {{#emp}}
    <tr>
        <td colspan="4">
            <b>Ответственный менеджер:</b> {{emp}}
        </td>
    </tr>
    {{/emp}}
    <tr>
        <td colspan="4">
            <b>Дата  окончания:</b> {{dateend}}
        </td>
    </tr>
    
    <tr>
        <td colspan="4">
            {{notes}}
        </td>
    </tr>


</table>


