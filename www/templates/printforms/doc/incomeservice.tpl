<table class="ctable"  >
    <tr>
        <td colspan="6">
            Виконавець: {{customer_name}}
        </td>


    </tr>
    {{#isfirm}}
    <tr>
        <td colspan="6">
          Замовник: {{firm_name}}
        </td>

    </tr>
    {{/isfirm}}
    {{#iscontract}}
    <tr>
        <td colspan="6">
            Договір: {{contract}} вiд {{createdon}}
        </td>

    </tr>
    {{/iscontract}}

 
   <tr>
        <td colspan="6">{{{notes}}}</td>
    </tr>
    
    <tr style="font-weight: bolder;">
        <td colspan="6" align="center">
            Отриманi послуги № {{document_number}} від {{date}}
        </td>
    </tr>

</table>
<br>
<table class="ctable" width="600" cellspacing="0" cellpadding="1" border="0">
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;">Найменування</th>
        <th style="border: 1px solid black;">Опис</th>
        <th style="border: 1px solid black;" align="right">Кіл.</th>
        <th style="border: 1px solid black;" align="right">Вартість</th>
        <th style="border: 1px solid black;" align="right">Сума</th>
 
         
     
    </tr>
    {{#_detail}}
    <tr>
        <td valign="top">{{no}}</td>
        <td valign="top">{{service_name}}</td>

        <td  valign="top">{{desc}}</td>

        <td valign="top" align="right">{{qty}}</td>
        <td valign="top" align="right">{{price}}</td>
        <td valign="top" align="right">{{amount}}</td>
 

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td colspan="5" style="border-top: 1px solid black;" align="right">Всього:</td>
        <td style="border-top: 1px solid black;" align="right">{{total}}</td>
    </tr>
   {{#payamount}}
    <tr style="font-weight: bolder;">
        <td colspan="5" align="right">До сплати:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    {{/payamount}} 
   {{#payed}}  
    <tr style="font-weight: bolder;">
        <td colspan="5" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
     {{/payed}}  
    <tr>
        <td colspan="6"><br>
              {{notes}}
        </td>
    </tr>
   {{#hasitems}}
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;">Найменування</th>
        <th style="border: 1px solid black;">Код</th>
        <th style="border: 1px solid black;" align="right">Кіл.</th>
        <th style="border: 1px solid black;" align="right">Ціна</th>
        <th style="border: 1px solid black;" align="right">Сума</th>
 
         
     
    </tr>
    {{#_detail2}}
    <tr>
        <td valign="top">{{no}}</td>
        <td valign="top">{{itemname}}</td>

        <td  valign="top">{{item_code}}</td>

        <td valign="top" align="right">{{qty}}</td>
        <td valign="top" align="right">{{price}}</td>
        <td valign="top" align="right">{{amount}}</td>
 

    </tr>
    {{/_detail2}}   
   
    <tr style="font-weight: bolder;">
        <td colspan="5" align="right">Всього:</td>
        <td align="right">{{stotal}}</td>
    </tr>   
   {{/hasitems}}
</table>


