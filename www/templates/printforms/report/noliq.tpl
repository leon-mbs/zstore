<table class="ctable" border="0"   cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="7">
            Неліквідні товари
        </td>
    </tr>
 
    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px"> Найменування</th>

        <th style="border: solid black 1px">Код</th>
        <th style="border: solid black 1px">Категорiя</th>
        <th style="border: solid black 1px">Бренд</th>
        <th style="border: solid black 1px">Склад</th>
        <th align="right" style="border: solid black 1px">Цiна</th>
        <th align="right" style="border: solid black 1px">На складi</th>


    </tr>
    {{#_detail}}
    <tr>


        <td>{{itemname}}</td>
        <td>{{item_code}}</td>
        <td>{{cat_name}}</td>
        <td>{{brand}}</td>
        <td>{{store}}</td>

        <td align="right">{{price}}</td>
        <td align="right">{{qty}}</td>


    </tr>
    {{/_detail}}


  

</table>
<br>  

