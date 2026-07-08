<table border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="2">
            Звiт про робочий час
        </td>
    </tr>
    <tr>

        <td align="center">
            <b> Перiод з {{from}} по {{to}}   </b>
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
        <td class="text-end">{{tm}}</td>

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">

        <td class="text-end">Всього:</td>

        <td class="text-end">{{total}}</td>

    </tr>

</table>


