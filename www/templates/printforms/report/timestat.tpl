<table border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="2">
            Отчет по рабочему времени
        </td>
    </tr>
    <tr>

        <td align="center">
            <b> Период с {{from}} по {{to}}   </b>
        </td>
    </tr>
    <tr>

        <td colspan="2">
            <b>  {{typename}}   </b>
        </td>
    </tr>


    {{#_detail}}
    <tr>

        <td>{{emp_name}}</td>
        <td class="text-right">{{tm}}</td>

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">

        <td class="text-right">Итого:</td>

        <td class="text-right">{{total}}</td>

    </tr>

</table>


