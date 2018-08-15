 
<h3>Звіт  по  продажам з {{datefrom}} по {{dateto}}  </h3>

<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr  > <th style="border-bottom: 1px solid black;">Товар</th>
        <th  style="border-bottom: 1px solid black;">Кол.</th>
        <th  style="border-bottom: 1px solid black;">&nbsp;Цена</th>
        <th style="border-bottom: 1px solid black;">Сумма</th> 

        <th style="border-bottom: 1px solid black;">Прибыль</th></tr>
            {{#list}}
    <tr  > <td>{{productname}}</td>
        <td align="right">{{qty}}</td>
        <td align="right">&nbsp;&nbsp;{{price}}</td>
        <td align="right">&nbsp;&nbsp;{{amount}}</td> 
        <td align="right">&nbsp;&nbsp;{{profit}}</td></tr>
        {{/list}}
    <tr  ><td colspan="3" align="right" style="font-weight: bolder;border-top: 1px solid black;" >Итого:</td>
        <td style="font-weight: bolder;border-top: 1px solid black;"  align="right">{{total}}</td>
        <td style="font-weight: bolder;border-top: 1px solid black;"  align="right">{{ptotal}} </td>

    </tr>

</table>


