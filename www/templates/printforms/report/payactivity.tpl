<table border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="6">
            Рух по грошовим рахункам
        </td>
    </tr>
    <tr>

        <td align="center" colspan="6">
            Період з {{datefrom}} по {{dateto}}&nbsp;&nbsp;&nbsp;&nbsp; Рахунок: <strong>{{mf_name}}</strong>
        </td>
    </tr>

    <tr style="font-weight: bolder;">

        <th style="border: solid black 1px">Дата</th>


        <th align="right" style="border: solid black 1px">Поч.</th>
        <th style="border: solid black 1px">Прих.</th>
        <th align="right" style="border: solid black 1px">Витр.</th>
        <th align="right" style="border: solid black 1px">Кін.</th>

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

        <td align="right">Разом:</td>


        <td align="right"></td>
        <td align="right">{{tin}}</td>
        <td align="right">{{tout}}</td>
        <td align="right"></td>
        <td></td>
    </tr>

</table>


