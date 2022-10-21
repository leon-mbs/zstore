<table class="ctable" border="0" cellpadding="2" cellspacing="0">
    <tr>
        <td colspan="5">
            Виконавець: {{customer_name}}
        </td>


    </tr>
    {{#isfirm}}
    <tr>
        <td colspan="5">
          Замовник: {{firm_name}}
        </td>

    </tr>
    {{/isfirm}}
    {{#iscontract}}
    <tr>
        <td colspan="5">
            Угода: {{contract}} вiд {{createdon}}
        </td>

    </tr>
    {{/iscontract}}

    {{#isdevice}}
    <tr>
        <td colspan="5">
            Виріб, матеріали: {{device}} с/н: {{devsn}}
        </td>

    </tr>
    {{/isdevice}}
   <tr>
        <td colspan="5">{{{notes}}}</td>
    </tr>
    
    <tr style="font-weight: bolder;">
        <td colspan="5" align="center">
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
        <th style="border: 1px solid black;" align="right">Вартість</th>
        <th style="border: 1px solid black;" align="right">Сума</th>
        {{#hasitems}}
        <th style="border: 1px solid black;" align="right">ТМЦ</th>
            
        {{/hasitems}}
    </tr>
    {{#_detail}}
    <tr>
        <td valign="top">{{no}}</td>
        <td valign="top">{{service_name}}</td>

        <td  valign="top">{{desc}}</td>

        <td valign="top" align="right">{{price}}</td>
        <td valign="top" align="right">{{amount}}</td>
         {{#hasitems}}  
           <td  > 
           <table style="font-size:smaller"  >
               <tr> <td >Найменування</td>
                <td align="right">&nbsp;Кіл.&nbsp;</td>
                <td align="right">Ціна.</td></tr>
             {{#items}}
               <tr> <td  >{{itemname}}</td>
                <td align="right">{{qty}}</td>
                <td align="right">{{price}}</td>
                </tr>
             {{/items}}
             </table>
           </td>
         {{/hasitems}}

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td colspan="4" style="border-top: 1px solid black;" align="right">Всього:</td>
        <td style="border-top: 1px solid black;" align="right">{{total}}</td>
    </tr>
   {{#payamount}}
    <tr style="font-weight: bolder;">
        <td colspan="4" align="right">До оплати:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    {{/payamount}} 
   {{#payed}}  
    <tr style="font-weight: bolder;">
        <td colspan="4" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
     {{/payed}}  
    <tr>
        <td colspan="5"><br>
            Гарантія: {{gar}}
        </td>
    </tr>

</table>


