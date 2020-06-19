<table border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="6">
            Движение по контрагентам
        </td>
    </tr>
    <tr>

        <td align="center" colspan="6">
            Период с {{datefrom}} по {{dateto}}&nbsp;&nbsp;&nbsp;&nbsp; Контрагент: <strong>{{cust_name}}</strong>
        </td>
    </tr>

    <tr style="font-weight: bolder;">

        <th style="border: solid black 1px">Документ</th>
        <th style="border: solid black 1px">Дата</th>


        <th style="border: solid black 1px">Приход</th>
        <th align="right" style="border: solid black 1px">Расход</th>


    </tr>
    {{#_detail}}
    <tr>

        <td>{{document_number}}</td>
        <td>{{date}}</td>


        <td align="right">{{obin}}</td>
        <td align="right">{{obout}}</td>


    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">

        <td align="right"></td>
        <td align="right">Итого:</td>


        <td align="right">{{tin}}</td>
        <td align="right">{{tout}}</td>

        <td></td>
    </tr>

</table>


