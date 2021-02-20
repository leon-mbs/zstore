<table class="ctable" border="0" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Неликвидные товары за последние {{mqty}} мес.
        </td>
    </tr>

    <tr style="font-weight: bolder;">

        <th style="border: solid black 1px"> Склад</th>

        <th style="border: solid black 1px">Товар</th>
        <th style="border: solid black 1px">Код</th>
        <th align="right" style="border: solid black 1px">Кол.</th>

    </tr>
    {{#_detail}}
    <tr>

        <td>{{storename}}</td>
        <td>{{itemname}}</td>
        <td>{{item_code}}</td>

        <td align="right">{{qty}}</td>

    </tr>
    {{/_detail}}


</table>
<br> <br>
</body>
</html>
