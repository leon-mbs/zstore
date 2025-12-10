<table class="ctable" border="0" cellspacing="0" cellpadding="2">

    {{#islogo}}
    <tr>

        <td colspan="9">
            <img style="height:100px;" src="{{logo}}"/>
        </td>

    </tr>
    {{/islogo}}
    <tr>
        <td></td>
        <td><b>Покупець</b></td>
        <td colspan="7"> {{customer_name}}</b> 
          {{#phone}} Тел. {{phone}}  {{/phone}} 
        </td>
    </tr>
    
 
 
   {{#edrpou}}
    <tr>
        <td></td>
        <td valign="top">ЄДРПОУ</td>
        <td colspan="7">{{edrpou}}</td>
    </tr>
     {{/edrpou}}       
          
     {{#iscustaddress}}
    
      <tr>
        <td></td>
        <td valign="top">Адреса</td>
        <td colspan="7">{{custaddress}}</td>
    </tr>    
    {{/iscustaddress}}   
    
    {{#isfirm}}
    <tr>

        <td></td>
        <td><b> Продавець</b></td>
        <td colspan="7"> {{firm_name}} 
        {{#fphone}} Тел.  {{fphone}}  {{/fphone}} 
          
        </td>

    </tr>
 
    {{#fedrpou}}
    <tr>
        <td></td>
        <td valign="top">ЄДРПОУ</td>
        <td colspan="7">{{fedrpou}}</td>
    </tr>
     {{/fedrpou}}  
    
     {{#finn}}
    <tr>
        <td></td>
        <td valign="top">IПН</td>
        <td colspan="7">{{finn}}</td>
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
    {{#iscontract}}
    <tr>

        <td></td>
        <td> Договір</td>
        <td colspan="7">{{contract}} вiд {{createdon}}</td>

    </tr>
    {{/iscontract}}
    {{#isbank}}
    <tr>

        <td></td>
        <td> р/р</td>
        <td colspan="7">{{bankacc}}    {{bank}}</td>

    </tr>
    {{/isbank}}
     {{#iban}}
    <tr>

        <td></td>
        <td> IBAN</td>
        <td colspan="7">{{iban}}   </td>

    </tr>
    {{/iban}}
   <tr>
        <td colspan="9">{{{notes}}}</td>
    </tr>
    
    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="9" valign="middle">
            Рахунок-фактура № {{document_number}} від {{date}}
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Найменування
        </th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Од.</th>

        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Кіл.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Ціна  {{#nds}} (без ПДВ)  {{/nds}} </th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td colspan="2">{{tovar_name}}</td>
        <td colspan="2">{{tovar_code}}</td>
        <td>{{msr}}</td>

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}  {{#nds}} ({{pricenonds}})  {{/nds}}   </td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="8" align="right">Разом:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>
    {{#totaldisc}}
    <tr style="font-weight: bolder;">
        <td colspan="8" align="right">Знижка:</td>
        <td align="right">{{totaldisc}}</td>
    </tr>
    {{/totaldisc}}
   {{#nds}}
    <tr style="font-weight: bolder;">
        <td colspan="8" align="right">В т.ч. ПДВ:</td>
        <td align="right">{{nds}}</td>
    </tr>
    {{/nds}}
   {{#payamount}}
    <tr style="font-weight: bolder;">
        <td colspan="8" align="right">До сплати:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    {{/payamount}} 
   {{#payed}}  
    <tr style="font-weight: bolder;">
        <td colspan="8" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
     {{/payed}}  
       {{#payamount}}
   {{#totalstr}}
    <tr>
        <td colspan="9">На суму <b>{{totalstr}}</b></td>
   </tr>
   {{/totalstr}}      
  {{/payamount}}
    <tr>                  <td colspan="5">
                            {{#isstamp}}
                            <img style="height:100px;" src="{{stamp}}"/>
                            {{/isstamp}}


                        </td>
                        <td colspan="4">
                            {{#issign}}
                            <img style="height:100px;" src="{{sign}}"/>
                            {{/issign}}


                        </td>

                    </tr>
          
                             
                    </table>

