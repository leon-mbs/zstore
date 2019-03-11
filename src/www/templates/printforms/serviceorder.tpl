 
<table class="ctable"   border="0" cellpadding="2" cellspacing="0">
    <tr>
        <td colspan="6" >
            Заказчик:  {{customer}}
        </td>
 
         
    </tr>
    <tr>
        <td colspan="6"  style="font-weight: bolder;font-size: larger;" align="center"     >
            Заказ № {{document_number}} от {{date}}   
        </td>
    </tr>  

    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;"  >Наименование</th>
        <th style="border: 1px solid black;"  >Описание</th>

        <th style="border: 1px solid black;"   align="right">Кол.</th>
        <th style="border: 1px solid black;"   align="right">Цена</th>
        <th style="border: 1px solid black;"   align="right">Сумма</th>
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
   

</table>


