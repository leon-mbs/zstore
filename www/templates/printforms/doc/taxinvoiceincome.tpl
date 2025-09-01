<table class="ctable" border="0" cellspacing="0" cellpadding="2">
  

    <tr>
        <td></td>
        <td>Постачальник</td>
        <td colspan="5">{{customername}}</td>
    </tr>
 
    <tr>
        <td></td>
        <td>Отримувач</td>
        <td colspan="5">{{firmname}}</td>
    </tr>

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="7" valign="middle">
            <br><br> Вхідна ПН  № {{document_number}} від {{date}} 
        </td>
    </tr>
</table>
<br><br> 
<table class="ctable" width="600" cellspacing="0" cellpadding="1" border="0">
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;"  >Назва</th>
        <th style="border: 1px solid black;"  >Од.</th>
        <th style="border: 1px solid black;"  >Кіл.</th>
        <th style="border: 1px solid black;"  >Ціна-</th>
        <th style="border: 1px solid black;"  >Ціна+</th>
        <th style="border: 1px solid black;"  >Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td>{{no}}</td>
        <td>{{itemname}}</td>
        <td>{{measure}}</td>
        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{pricends}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td colspan="6" style="border-top: 1px solid black;" align="right">Вього:</td>
        <td   style="border-top: 1px solid black;" align="right">{{total}} </td>
    </tr>
 
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">  ПДВ:</td>
        <td align="right">{{totalnds}} </td>
    </tr>
 
   <tr style="font-weight: bolder;">
        <td colspan="6" align="right">  Разом:</td>
        <td align="right">{{totalall}} </td>
    </tr>
 


</table>




