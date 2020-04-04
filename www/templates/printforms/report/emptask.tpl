 
<table class="ctable"   border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="5">
            Оплата  по  нарядам 
        </td>
    </tr>            
    <tr>

        <td align="center" colspan="5">
            Период с {{datefrom}} по {{dateto}}
        </td>
    </tr>

    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px" >Исполнитель</th>

        <th align="right" style="border: solid black 1px">Нарядов</th>
        <th align="right" style="border: solid black 1px">Часов</th>

        <th align="right" style="border: solid black 1px">Сумма</th>
        <th align="right" style="border: solid black 1px">К оплате</th>

        {{#_detail}}
    <tr>


        <td>{{name}}</td>

        <td align="right">{{cnt}}</td>
        <td align="right">{{hours}}</td>

        <td align="right">{{amount}}</td>
        <td align="right">{{amountpay}}</td>

    </tr>
    {{/_detail}}
</table>


<br> <br>
</body>
</html>
