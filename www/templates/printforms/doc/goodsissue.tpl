<table class="ctable" border="0" cellspacing="0" cellpadding="2"  >

     {{#customer_name}}
    <tr>
        <td></td>
        <td valign="top"><b>Покупець</b></td>
        <td colspan="5"><b>{{customer_name}}</b></td>
    </tr>
      {{/customer_name}} 
      
    {{#phone}}
    <tr>
        <td></td>
        <td valign="top">Телефон</td>
        <td colspan="5">{{phone}}</td>
    </tr>
     {{/phone}} 
   {{#address}}
    <tr>
        <td></td>
        <td valign="top">Адреса</td>
        <td colspan="5">{{address}}</td>
    </tr>
     {{/address}} 
   {{#edrpou}}
    <tr>
        <td></td>
        <td valign="top">ЄДРПОУ</td>
        <td colspan="5">{{edrpou}}</td>
    </tr>
     {{/edrpou}}       
      
      
      
      
    {{#isfirm}}
    <tr>
        <td></td>

        <td valign="top"><b>Продавець</b></td>
        <td colspan="5"><b>{{firm_name}}</b></td>

    </tr>
   {{#fedrpou}}
    <tr>
        <td></td>
        <td valign="top">ЄДРПОУ</td>
        <td colspan="5">{{fedrpou}}</td>
    </tr>
     {{/fedrpou}}  
    {{#finn}}
    <tr>
        <td></td>
        <td valign="top">IПН</td>
        <td colspan="5">{{finn}}</td>
    </tr>
     {{/finn}}           
    {{#isbank}}
    <tr>

        <td></td>
        <td> р/р</td>
        <td colspan="7">{{bankacc}}    {{bank}}</td>

    </tr>
    {{/isbank}}    
    {{/isfirm}}
    {{#iscontract}}
    <tr>

        <td></td>

        <td valign="top"><b>Угода</b></td>
        <td colspan="5">{{contract}} вiд {{createdon}}</td>


    </tr>
    {{/iscontract}}
    
    <tr>
        <td></td>
        <td valign="top"><br>Зписано з</td>
        <td colspan="5"><br>{{store_name}}</td>
    </tr>
 
    {{#order}}
    <tr>
        <td></td>
        <td><b>Замовлення</b></td>
        <td colspan="5">{{order}}</td>
    </tr>
    {{/order}}
    <tr>
        <td colspan="7">{{{notes}}}</td>
    </tr>


    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="7" valign="middle">
            Накладна № {{document_number}} від {{date}} <br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Найменування</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>

        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Кіл.</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Ціна</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td>{{tovar_name}}</td>
        <td>{{tovar_code}}</td>
        <td>{{msr}}</td>

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="2">{{weight}}</td>

        <td style="border-top:1px #000 solid;" colspan="4" align="right">На суму:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>

   
    {{#isdisc}}
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">Знижка:</td>
        <td align="right">{{paydisc}}</td>
    </tr>
    {{/isdisc}}


   {{#payamount}}
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">Всього:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    {{/payamount}} 
     {{#isprep}}  
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">Передплата:</td>
        <td align="right">{{prepaid}}</td>
    </tr>
     {{/isprep}}      
   {{#payed}}  
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
     {{/payed}}  
       {{#payamount}}
   
   {{#totalstr}}
    <tr>
        <td colspan="7">На суму <b>{{totalstr}}<b></td>
   </tr>
   {{/totalstr}}                    

              {{/payamount}} 
                    <tr>
                        <td colspan="4" > 
                            Продавець ___________
                        </td>
                        <td colspan="3"> 
                            Покупець ___________
                        </td>

                    </tr>
                    <tr>
                        <td> </td>
                        <td colspan="6">
                            <br><br>
                            &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;    &nbsp; МП
                            <br><br>
                        </td>


                    </tr>
                     <tr>
                        <td colspan="3" > 
                            {{{docbarcode}}}
                        </td>
                        <td colspan="4"> 
                            {{{docqrcode}}}
                        </td>

                    </tr>

                    </table>

