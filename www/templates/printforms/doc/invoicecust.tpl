<table class="ctable" border="0" cellspacing="0" cellpadding="2">


    <tr>
        <td></td>
        <td>Постачальник</td>
        <td colspan="5">{{customer_name}}</td>
    </tr>
    {{#isfirm}}
    <tr>
        <td></td>

        <td valign="top"><b>Покупець</b></td>
        <td colspan="5">{{firm_name}}</td>

    </tr>
    {{/isfirm}}
    {{#iscontract}}
    <tr>

        <td></td>

        <td valign="top"><b>Договір</b></td>
        <td colspan="5">{{contract}} вiд {{createdon}}</td>


    </tr>
    {{/iscontract}}
    {{#isval}}
    <tr style="font-weight: bolder;">
       <td></td>
        <td><b>Валюта</b></td>
        <td colspan="5">{{valname}}   ({{rate}})</td>
        
        
    </tr>
    {{/isval}}
   <tr>
        <td colspan="7">{{{notes}}}</td>
    </tr>
            
    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="7" valign="middle">
            <br> Рахунок вхідний № {{document_number}} від {{date}} <br><br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Найменування</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Код продавця</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>

        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="50">Кіл.</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Ціна</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td>{{itemname}}</td>
        <td>{{itemcode}}</td>
        <td>{{custcode}}</td>
        <td>{{msr}}</td>
        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="7" align="right">Разом:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>
    {{#isdisc}}
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">Знижка:</td>
        <td align="right">{{disc}}</td>
    </tr>
    {{/isdisc}}
    {{#isnds}}
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">ПДВ:</td>
        <td align="right">{{nds}}</td>
    </tr>
     {{/isnds}}


   {{#payamount}}
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">До сплати:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    {{/payamount}} 
   {{#payed}}  
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
     {{/payed}}  


</table>

