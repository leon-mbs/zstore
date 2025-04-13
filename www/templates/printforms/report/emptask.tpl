<table class="ctable" border="0"   cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="5">
            Оплата по виробництву
        </td>
    </tr>
    <tr>

        <td align="center" colspan="5">
            Період з {{datefrom}} по {{dateto}}
        </td>
 
    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px">Виконавець</th>

        <th align="right" style="border: solid black 1px">Нарядів</th>
        <th align="right" style="border: solid black 1px">Годин</th>

        <th align="right" style="border: solid black 1px">Сума</th>

    </tr>
        {{#_detail}}
    <tr>


        <td>{{name}}</td>

        <td align="right">{{cnt}}</td>
        <td align="right">{{hours}}</td>

        <td align="right">{{amount}}</td>


    </tr>
    {{/_detail}}
 
  
</table>


<br> <br>

