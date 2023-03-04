<table class="ctable" border="0" cellpadding="2" cellspacing="0">
    <tr style="font-weight: bolder;">
        <td colspan="6" align="center">
            Наряд № {{document_number}} вiд {{document_date}}
        </td>
    </tr>
    {{#pareaname}}
    <tr>
        <td colspan="6">
            Вироб. ділянка: {{pareaname}}
        </td>

    </tr>
    {{/pareaname}}

    {{#baseddoc}}
    <tr>
        <td colspan="6">
            Замовлення: {{baseddoc}}
        </td>

    </tr>
    {{/baseddoc}}

    {{#cust}}
    <tr>
        <td colspan="6">
            Замовник: {{cust}}
        </td>

    </tr>
    {{/cust}}

    <tr style="font-weight: bolder;">

        <th colspan="6" style="text-align: left;">Роботи</th>

    </tr>
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;">Найменування</th>
        <th style="border: 1px solid black;">Категорiя</th>
        <th style="border: 1px solid black;" width="50" align="right">Кiл</th>
        <th style="border: 1px solid black;" width="50" align="right">Сума</th>
        <th style="border: 1px solid black;" width="50" align="right">Годин</th>
        <th style="border: 1px solid black;"   > </th>


    </tr>
    {{#_detail}}
    <tr>
        <td>{{no}}</td>
        <td>{{service_name}}</td>
        <td>{{category}}</td>
        <td align="right">{{quantity}}</td>
        <td align="right">{{cost}}</td>
        <td align="right">{{hours}}</td>
        <td  >{{desc}}</td>


    </tr>
    {{/_detail}}

    <tr style="font-weight: bolder;">

        <th colspan="6" style="text-align: left;">Готова продукцiя</th>

    </tr>
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;">Найменування</th>
        <th style="border: 1px solid black;">Код</th>
        <th style="border: 1px solid black;" width="50" align="right">Кiл.</th>
        <th style="border: 1px solid black;" width="50" align="right"> </th>
        <th style="border: 1px solid black;" width="50" align="right"> </th>
        <th style="border: 1px solid black;"    > </th>


    </tr>
    {{#_detailprod}}
    <tr>
        <td>{{no}}</td>
        <td>{{itemname}}</td>
        <td>{{item_code}}</td>
        <td align="right">{{quantity}}</td>
        <td align="right"> </td>
        <td align="right"> </td>
        <td  >{{desc}}</td>


    </tr>
    {{/_detailprod}}

    <tr style="font-weight: bolder;">

        <th colspan="6" style="text-align: left;">Виконавці</th>

    </tr>
    {{#_detail3}}
    <tr>

        <td colspan="4">{{emp_name}}</td>
       <td colspan="2">{{emp_ktu}}</td>


    </tr>
    {{/_detail3}}


    {{#iseq}}

    <tr style="font-weight: bolder;">

        <th colspan="6" style="text-align: left;">Устаткування</th>

    </tr>
    {{#_detail2}}
    <tr>

        <td colspan="4">{{eq_name}}</td>

        <td colspan="2">{{code}}</td>

    </tr>
    {{/_detail2}}
    {{/iseq}}
    <tr>
        <td colspan="6">
            {{{notes}}}
        </td>

    </tr>

</table>


