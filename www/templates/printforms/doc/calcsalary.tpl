<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td  colspan="{{colspan}}">
            <b> Нарахування зарплати № {{document_number}} від {{date}}</b> <br>
        </td>
    </tr>

   

    <tr>
        <td  colspan="{{colspan}}">
            <b>Місяць:</b> {{month}} {{year}}
        </td>
    </tr>

    <tr>
        <td  colspan="{{colspan}}">
            {{{notes}}}
        </td>
    </tr>
    <tr>
        <td>
            <b>П I Б</b>
        </td>
        {{#stnames}}
        <td class="text-right">
            <b>{{name}}</b>
        </td>
        {{/stnames}}
    </tr>

    {{#_detail}}
    <tr>
        <td>
            {{emp_name}}
        </td>
        {{#amounts}}
                <td class="text-right">
            {{am}}
        </td>
        {{/amounts}}
    </tr>

    {{/_detail}}
    <tr>
        <td colspan="{{colspan}}">
            <b>Всього:  {{total}}</b>
        </td>
         
    </tr>

</table>


