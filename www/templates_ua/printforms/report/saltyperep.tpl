<table border="0" class="ctable" cellpadding="3" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="2">
           Звiт по нарахуванням  та  утриманням
        </td>
    </tr>
    <tr>

        <td align="center" colspan="3">
            Перiод з {{mfrom}} {{yfrom}} по {{mto}} {{yto}}
        </td>
    </tr>
    {{#isemp}}
    <tr>

        <td style="font-weight: bolder;" colspan="3">
            {{emp_name}}
        </td>
    </tr>
    {{/isemp}}

    {{#_detail}}
    <tr>

        <td>{{code}}</td>
        <td>{{name}}</td>
        <td class="text-right">{{am}}</td>

    </tr>
    {{/_detail}}

</table>


