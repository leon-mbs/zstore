<table class="ctable" border="0" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Комісійні товари  на {{dt}}
        </td>
    </tr>
    
    {{#iscust}}
    <tr >
        <td  colspan="3">
          <b> Комітент:</b>  {{cust}}
            <br>
            <br>
        </td>
    </tr>
    {{/iscust}}
    
    <tr style="font-weight: bolder;">

         
        <th style="border: solid black 1px">Товар</th>
        <th style="border: solid black 1px">Код</th>
        <th style="border: solid black 1px">Ціна</th>
        <th align="right" style="border: solid black 1px">Придбано</th>
        <th align="right" style="border: solid black 1px">Продано</th>
        <th align="right" style="border: solid black 1px">Повернуто</th>
        <th align="right" style="border: solid black 1px">Накладна</th>

    </tr>
    {{#_detail}}
    <tr>

   
        <td>{{itemname}}</td>
        <td>{{item_code}}</td>

        <td align="right">{{price}}</td>
        <td align="right">{{buyqty}}</td>
        <td align="right">{{sellqty}}</td>
        <td align="right">{{retqty}}</td>
        <td align="right">{{docs}}</td>

    </tr>
    {{/_detail}}
 


</table>
<br> <br>

