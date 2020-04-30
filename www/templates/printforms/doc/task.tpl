<table class="ctable" border="0" cellpadding="2" cellspacing="0">
    <tr style="font-weight: bolder;">
        <td colspan="4" align="center">
            Наряд № {{document_number}} с {{startdate}} по {{date}}
        </td>
    </tr>
    {{#pareaname}}
    <tr>
        <td colspan="4">
            Произв. участок: {{pareaname}}
        </td>

    </tr>
    {{/pareaname}}

    {{#baseddoc}}
    <tr>
        <td colspan="4">
            Заказ: {{baseddoc}}
        </td>

    </tr>
    {{/baseddoc}}


    <tr style="font-weight: bolder;">

        <th colspan="4" style="text-align: left;">Работы</th>

    </tr>
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;">Наименование</th>
        <th style="border: 1px solid black;" width="50" align="right"></th>
        <th style="border: 1px solid black;" width="50" align="right">Часов</th>


    </tr>
    {{#_detail}}
    <tr>
        <td>{{no}}</td>
        <td>{{service_name}}</td>
        <td align="right"></td>

        <td align="right">{{hours}}</td>


    </tr>
    {{/_detail}}


    <tr style="font-weight: bolder;">

        <th colspan="4" style="text-align: left;">Исполнители</th>

    </tr>
    {{#_detail3}}
    <tr>

        <td colspan="4">{{emp_name}}</td>


    </tr>
    {{/_detail3}}


    {{#iseq}}

    <tr style="font-weight: bolder;">

        <th colspan="4" style="text-align: left;">Оборудование</th>

    </tr>
    {{#_detail2}}
    <tr>

        <td colspan="2">{{eq_name}}</td>

        <td colspan="2">{{code}} </td>

    </tr>
    {{/_detail2}}
    {{/iseq}}
    <tr>
        <td colspan="4">
            {{notes}}
        </td>

    </tr>

</table>


