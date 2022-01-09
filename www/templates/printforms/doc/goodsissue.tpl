<table class="ctable" border="0" cellspacing="0" cellpadding="2"  {{{style}}}}>

     {{#customer_name}}
    <tr>
        <td></td>
        <td valign="top"><b>Покупатель</b></td>
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
        <td valign="top">Адрес</td>
        <td colspan="5">{{address}}</td>
    </tr>
     {{/address}} 
   {{#edrpou}}
    <tr>
        <td></td>
        <td valign="top">ОКПО</td>
        <td colspan="5">{{edrpou}}</td>
    </tr>
     {{/edrpou}} 
 
    
        
    {{#isfirm}}
    <tr>

        <td></td>
        <td valign="top"><b>Продавец</b></td>
        <td colspan="5"><b>{{firm_name}}</b></td>

    </tr>
    {{/isfirm}}
    {{#iscontract}}
    <tr>

        <td></td>
        <td valign="top"><b>Договор</b></td>
        <td colspan="5">{{contract}} от {{createdon}}</td>

    </tr>
    {{/iscontract}}

    <tr>
        <td></td>
        <td valign="top">Списано с</td>
        <td colspan="5">{{store_name}}</td>
    </tr>

    {{#order}}
    <tr>
        <td></td>
        <td><b>Заказ</b></td>
        <td colspan="5">{{order}}</td>
    </tr>
    {{/order}}

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="7" valign="middle">
            Накладная № {{document_number}} от {{date}} <br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Наименование</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Ед.</th>

        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Кол.</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Цена</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сумма</th>
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
        <td style="border-top:1px #000 solid;" colspan="4" align="right">Итого:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>

  
    {{#isdisc}}
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">Скидка:</td>
        <td align="right">{{paydisc}}</td>
    </tr>
    {{/isdisc}}

     {{#payamount}}
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">К оплате:</td>
        <td align="right">{{payamount}}</td>
    </tr>
     {{/payamount}}
      {{#payed}}  
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
        {{/payed}} 
        {{#payamount}}  
    <tr>
        <td colspan="7">На сумму <b>{{totalstr}}<b></td>

                    </tr>
              {{/payamount}}         
                    
                    <tr>
                        <td colspan="7"><b>Отпустил </b>__________________</td>
                    </tr>
                    <tr>
                        <td colspan="7">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="4" > 
                            Продавец ___________
                        </td>
                        <td colspan="3"> 
                            Покупатель ___________
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
                        <td colspan="7" > 
                            {{{docbarcode}}}
                
                        </td>

                    </tr>

                    </table>

