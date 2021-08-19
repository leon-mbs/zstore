<table border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Отчет по начислениям  и удержаниям
        </td>
    </tr>
    <tr>

        <td align="center" colspan="3">
            Период с {{mfrom}} {{yfrom}} по {{mto}} {{yto}}
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


