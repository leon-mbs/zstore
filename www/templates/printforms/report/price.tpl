<table class="ctable" border="0"   cellpadding="2" cellspacing="0">


    <tr>

        <td align="center" colspan="11">
          <b>  Прайс від {{date}}</b><br><br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">

        <th>Найменування</th>
        <th>Код</th>
        <th>Од. вим.</th>
        <th>Категорія</th>
        <th>Бренд</th>
        <th align="right">Кiл.</th>
        <th align="right">{{price1name}}</th>
        <th align="right">{{price2name}}</th>
        <th align="right">{{price3name}}</th>
        <th align="right">{{price4name}}</th>
        <th align="right">{{price5name}}</th>

    </tr>
    {{#_detail}}
    <tr>

        <td>{{name}}</td>
        <td>{{code}}</td>
        <td>{{msr}}</td>
        <td>{{cat}}</td>
        <td>{{brand}}</td>
        <td align="right">{{qty}}</td>
        <td align="right">{{price1}}</td>
        <td align="right">{{price2}}</td>
        <td align="right">{{price3}}</td>
        <td align="right">{{price4}}</td>
        <td align="right">{{price5}}</td>

    </tr>
    {{/_detail}}
</table>


