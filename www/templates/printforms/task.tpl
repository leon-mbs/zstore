 
<table class="ctable"   border="0" cellpadding="2" cellspacing="0">
    <tr style="font-weight: bolder;">
        <td colspan="6" align="center">
            Наряд № {{document_number}} с {{startdate}} по {{date}} 
        </td>
    </tr>   
    <tr>
        <td colspan="6">
            Заказчик:   {{customer}}
        </td>

    </tr>
    <tr>
        <td  colspan="6">
            Произв. участок:   {{pareaname}}
        </td>

    </tr>




    <tr style="font-weight: bolder;">

        <th colspan="6" style="text-align: left;">Работы и комплектующие </th>

    </tr>  
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th   style="border: 1px solid black;"  >Наименование</th>

        <th style="border: 1px solid black;" width="50" align="right">Часов</th>
        <th style="border: 1px solid black;" width="50" align="right">Кол.</th>
        <th style="border: 1px solid black;" width="50" align="right">Цена</th>
        <th style="border: 1px solid black;" width="50" align="right">Сумма</th>
    </tr>
    {{#_detail}}
    <tr>
        <td>{{no}}</td>
        <td  >{{servicename}}</td>

        <td align="right">{{hours}}</td>
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

        <th colspan="5" style="text-align: left;">Материалы </th>

    </tr>  
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th colspan="2" style="border: 1px solid black;"  >Наименование</th>

        <th style="border: 1px solid black;" width="50" align="right">Кол.</th>
        <th style="border: 1px solid black;" width="50" align="right">Цена</th>
        <th style="border: 1px solid black;" width="50" align="right">Сумма</th>
    </tr>          
    {{#_detail5}}
    <tr>
        <td>{{no}}</td>
        <td colspan="2">{{itemname}}</td>

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail5}}        

    <tr style="font-weight: bolder;">

        <th colspan="6" style="text-align: left;">Оборудование </th>

    </tr>          
    {{#_detail2}}
    <tr>

        <td colspan="3">{{eq_name}}</td>

        <td colspan="3" >{{code}} </td>

    </tr>
    {{/_detail2}}
    <tr style="font-weight: bolder;">

        <th colspan="6"  style="text-align: left;">Исполнители </th>

    </tr>
    {{#_detail3}}
    <tr>

        <td colspan="6">{{emp_name}}</td>



    </tr>
    {{/_detail3}}

    <tr style="font-weight: bolder;">
        <td   colspan="5" align="right">К оплате:</td>
        <td   align="right">{{payamount}}</td>
    </tr>    
    <tr style="font-weight: bolder;">
        <td   colspan="5" align="right">Оплата:</td>
        <td   align="right">{{payed}}</td>
    </tr>    
</table>


