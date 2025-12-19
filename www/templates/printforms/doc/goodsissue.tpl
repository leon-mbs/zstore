<table class="ctable" border="0" cellspacing="0" cellpadding="2"  >

     {{#customer_name}}
    <tr>
        <td></td>
        <td valign="top"><b>Покупець</b></td>
 
        <td colspan="6"> {{customer_name}}</b> 
          {{#phone}} Тел. {{phone}}  {{/phone}} 
        </td>
    </tr>
      {{/customer_name}} 
      
  
 
   {{#edrpou}}
    <tr>
        <td></td>
        <td valign="top">ЄДРПОУ</td>
        <td colspan="6">{{edrpou}}</td>
    </tr>
     {{/edrpou}}       
      
      {{#iscustaddress}}
    
      <tr>
        <td></td>
        <td valign="top">Адреса</td>
        <td colspan="6">{{custaddress}}</td>
    </tr>    
    {{/iscustaddress}}      
      
      
    {{#isfirm}}
    <tr>
        <td></td>

        <td valign="top"><b>Продавець</b></td>
       <td colspan="6"> {{firm_name}} 
        {{#fphone}} Тел.  {{fphone}}  {{/fphone}} 
          
        </td>

    </tr>
   {{#fedrpou}}
    <tr>
        <td></td>
        <td valign="top">ЄДРПОУ</td>
        <td colspan="6">{{fedrpou}}</td>
    </tr>
     {{/fedrpou}}  
    {{#finn}}
    <tr>
        <td></td>
        <td valign="top">IПН</td>
        <td colspan="6">{{finn}}</td>
    </tr>
     {{/finn}}           
   
      
    {{/isfirm}}
    {{#isfop}}
    <tr>

        <td></td>
        <td><b> Продавець</b></td>
        <td colspan="7"> {{fop_name}} </td>

    </tr> 
    <tr>
        <td></td>
        <td valign="top">ЄДРПОУ</td>
        <td colspan="7">{{fop_edrpou}}</td>
    </tr>       
   {{/isfop}}    
   
     <tr>
        <td></td>
        <td valign="top">Адреса</td>
        <td colspan="7">{{address}}</td>
    </tr>      
    {{#isbank}}
    <tr>

        <td></td>
        <td> р/р</td>
        <td colspan="8">{{bankacc}}    {{bank}}</td>

    </tr>
    {{/isbank}}  
     {{#iban}}
    <tr>

        <td></td>
        <td> IBAN</td>
        <td colspan="8">{{iban}}   </td>

    </tr>
    {{/iban}}     
    {{#iscontract}}
    <tr>

        <td></td>

        <td valign="top"><b>Договір</b></td>
        <td colspan="6">{{contract}} вiд {{createdon}}</td>


    </tr>
    {{/iscontract}}
    
    <tr>
        <td></td>
        <td valign="top"><br>Списано з</td>
        <td colspan="6"><br>{{store_name}}</td>
    </tr>
 
    {{#order}}
    <tr>
        <td></td>
        <td><b>Замовлення</b></td>
        <td colspan="6">{{order}}</td>
    </tr>
    {{/order}}
    <tr>
        <td colspan="8">{{{notes}}}</td>
    </tr>


    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="8" valign="middle">
            Видаткова накладна № {{document_number}} від {{date}} <br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Найменування</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>

        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Кіл.</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Зн. %</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Ціна  {{#nds}} (без ПДВ)  {{/nds}}</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td>{{tovar_name}}</td>
        <td>{{tovar_code}}</td>
        <td>{{msr}}</td>

        <td align="right">{{quantity}}</td>
        <td align="right">{{disc}}</td>
        <td align="right">{{price}}  {{#nds}} ({{pricenonds}})  {{/nds}} </td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="2">{{weight}}</td>

        <td style="border-top:1px #000 solid;" colspan="5" align="right">На суму:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>

    {{#totaldisc}}
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">Знижка:</td>
        <td align="right">{{totaldisc}}</td>
    </tr>
    {{/totaldisc}}
   {{#nds}}
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">В т.ч. ПДВ:</td>
        <td align="right">{{nds}}</td>
    </tr>
    {{/nds}}

   {{#payamount}}
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">Всього:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    {{/payamount}} 
     {{#isprep}}  
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">Передплата:</td>
        <td align="right">{{prepaid}}</td>
    </tr>
     {{/isprep}}      
   {{#payed}}  
    <tr style="font-weight: bolder;">
        <td colspan="7" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
     {{/payed}}  
       {{#payamount}}
   
   {{#totalstr}}
    <tr>
        <td colspan="8">На суму <b>{{totalstr}}</b></td>
   </tr>
   {{/totalstr}}                    

              {{/payamount}} 
                    <tr>
                        <td colspan="4" > 
                            Продавець ___________
                        </td>
                        <td colspan="4"> 
                            Покупець ___________
                        </td>

                    </tr>
                    <tr>
                        <td> </td>
                        <td colspan="7">
                            {{#isstamp}}
                            <img style="height:100px;" src="{{stamp}}"/>
                            {{/isstamp}}

                            {{^isstamp}}
                        
                            <br><br>
                            &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;    &nbsp; М.П.
                            <br><br>
                            {{/isstamp}}
                        </td>


                    </tr>
          
              
                          
                    </table>

