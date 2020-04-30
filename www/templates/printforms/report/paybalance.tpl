<table border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="2">
            Платежный баланс
        </td>
    </tr>
    <tr>

        <td align="center" colspan="2">
            Период с {{datefrom}} по {{dateto}} <br>
        </td>
    </tr>
    <tr>

        <td colspan="2">
            <b>Доходы</b>
        </td>
    </tr>


    {{#_detail}}
    <tr>

        <td>{{type}}</td>

        <td align="right">{{in}} </td>

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">

        <td align="right">Итого:</td>

        <td align="right">{{tin}} </td>

    </tr>
    <tr>

        <td colspan="2">
            <b>Расходы</b>>
        </td>
    </tr>


    {{#_detail2}}
    <tr>

        <td>{{type}}</td>

        <td align="right">{{out}} </td>

    </tr>
    {{/_detail2}}
    <tr style="font-weight: bolder;">

        <td align="right">Итого:</td>

        <td align="right">{{tout}} </td>

    </tr>
    <tr style="font-weight: bolder;">

        <td align="right">Баланс:</td>

        <td align="right">{{total}} </td>

    </tr>

</table>


