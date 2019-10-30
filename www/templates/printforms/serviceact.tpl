 
<table class="ctable"   border="0" cellpadding="2" cellspacing="0">
    <tr>
        <td colspan="6" >
            Заказчик:  {{customer}}
        </td>


    </tr>
    <tr>
        <td colspan="6" >
            Заказ:  {{order}}
        </td>


    </tr>

    <tr style="font-weight: bolder;">
        <td colspan="6" align="center">
            Акт выполненых работ № {{document_number}} от  {{date}}       </td>
    </tr>

</table>
<br>
<table class="ctable" width="600" cellspacing="0" cellpadding="1" border="0">
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;"  >Наименование</th>
        <th style="border: 1px solid black;"  >Описание</th>

        <th style="border: 1px solid black;" width="50" align="right">Кол.</th>
        <th style="border: 1px solid black;" width="50" align="right">Цена</th>
        <th style="border: 1px solid black;" width="50" align="right">Сумма</th>
    </tr>
    {{#_detail}}
    <tr>
        <td>{{no}}</td>
        <td>{{servicename}}</td>

        <td  >{{desc}}</td>
        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td colspan="5" style="border-top: 1px solid black;" align="right">Всего:</td>
        <td style="border-top: 1px solid black;" align="right">{{total}} </td>
    </tr>
    <tr style="font-weight: bolder;">
        <td   colspan="5" align="right">К оплате:</td>
        <td   align="right">{{payamount}}</td>
    </tr>    
    <tr style="font-weight: bolder;">
        <td   colspan="5" align="right">Оплата:</td>
        <td  " align="right">{{payed}}</td>
    </tr>
    <tr>
        <td colspan="6"><br>
            Гарантия: {{gar}}
        </td>    
    </tr>

</table>


