<table class="ctable" border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Неліквідні товари
        </td>
    </tr>
    {{#cust}}
    <tr>

        <td colspan="3">
            <b> Постачальник:</b> {{customer_name}}
        </td>
    </tr>
    {{/cust}}
    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px"> Найменування</th>

        <th style="border: solid black 1px">Од.</th>
        <th align="right" style="border: solid black 1px">На складi</th>


    </tr>
    {{#_detail}}
    <tr>


        <td>{{itemname}}</td>

        <td>{{msr}}</td>
        <td align="right">{{qty}}</td>


    </tr>
    {{/_detail}}


  

</table>
<br> <br>
</body>
</html>
