<table class="ctable" border="0" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Товары в пути на {{date}}
        </td>
    </tr>
    {{#cust}}
    <tr>

        <td colspan="3">
            <b> Поставщик:</b> {{customer_name}}
        </td>
    </tr>
    {{/cust}}
    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px"> Наименование</th>

        <th style="border: solid black 1px">Ед.</th>
        <th align="right" style="border: solid black 1px">Кол.</th>


    </tr>
    {{#_detail}}
    <tr>


        <td>{{name}}</td>

        <td>{{msr}}</td>
        <td align="right">{{qty}}</td>


    </tr>
    {{/_detail}}


    <tr>

        <td colspan="3">
            <b> На сумму:</b> {{total}}
        </td>
    </tr>

</table>
<br> <br>
</body>
</html>
