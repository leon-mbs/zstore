<table class="ctable" border="0" cellpadding="2" cellspacing="0">


    <tr>

        <th align="center" colspan="11">
            Прайс от {{date}}<br>
        </th>
    </tr>

    <tr style="font-weight: bolder;">

        <th>Наименование</th>
        <th>Код</th>
        <th>Ед.Изм.</th>
        <th>Категория</th>
        <th>Бренд</th>

        <th>{{price1name}}</th>
        <th>{{price2name}}</th>
        <th>{{price3name}}</th>
        <th>{{price4name}}</th>
        <th>{{price5name}}</th>
        <th>Кол.</th>

    </tr>
    {{#_detail}}
    <tr>

        <td>{{name}}</td>
        <td>{{code}}</td>
        <td>{{msr}}</td>
        <td>{{cat}}</td>
        <td>{{brand}}</td>

        <td align="right">{{price1}}</td>
        <td align="right">{{price2}}</td>
        <td align="right">{{price3}}</td>
        <td align="right">{{price4}}</td>
        <td align="right">{{price5}}</td>
        <td align="right">{{qty}}</td>
    </tr>
    {{/_detail}}
</table>


