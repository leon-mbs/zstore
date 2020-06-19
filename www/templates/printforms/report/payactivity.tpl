<table border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="6">
            Движение по денежным счетам
        </td>
    </tr>
    <tr>

        <td align="center" colspan="6">
            Период с {{datefrom}} по {{dateto}}&nbsp;&nbsp;&nbsp;&nbsp; Счет: <strong>{{mf_name}}</strong>
        </td>
    </tr>

    <tr style="font-weight: bolder;">

        <th style="border: solid black 1px">Дата</th>


        <th align="right" style="border: solid black 1px">Нач.</th>
        <th style="border: solid black 1px">Прих.</th>
        <th align="right" style="border: solid black 1px">Расх.</th>
        <th align="right" style="border: solid black 1px">Кон.</th>

    </tr>
    {{#_detail}}
    <tr>

        <td>{{date}}</td>


        <td align="right">{{in}}</td>
        <td align="right">{{obin}}</td>
        <td align="right">{{obout}}</td>
        <td align="right">{{out}}</td>

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">

        <td align="right">Итого:</td>


        <td align="right">{{tb}}</td>
        <td align="right">{{tin}}</td>
        <td align="right">{{tout}}</td>
        <td align="right">{{tend}}</td>
        <td></td>
    </tr>

</table>


