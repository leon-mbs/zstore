<table class="ctable" border="0" cellpadding="1" cellspacing="0"  {{{style}}}}>
    <tr style="font-weight: bolder;">
        <td colspan="3">Чек {{document_number}}</td>
    </tr>
    {{#fiscalnumber}}
    <tr>
        <td colspan="3">Фiскальний чек</td>
    </tr>
    <tr>
        <td colspan="3">ФН чека {{fiscalnumber}}</td>
    </tr>
    {{/fiscalnumber}}
   {{#fiscalnumberpos}}
    <tr>
        <td colspan="3">ФН РРО {{fiscalnumberpos}}</td>
    </tr>
    {{/fiscalnumberpos}}
   

    <tr>

        <td colspan="3">від {{time}}</td>
    </tr>
    <tr>

        <td colspan="3"> {{firm_name}}</td>
    </tr>
  {{#inn}}
    <tr>

        <td colspan="3">ІПН {{inn}}</td>
    </tr>
 {{/inn}} 
 {{#tin}}
    <tr>

        <td colspan="3">ЄДРПОУ {{tin}}</td>
    </tr>
 {{/tin}} 
    
    {{#shopname}}
    <tr>
        <td colspan="3"> {{shopname}}</td>
    </tr>
    {{/shopname}}
    <tr>

        <td colspan="3"> {{address}}</td>
    </tr>
    <tr>
        <td colspan="3"> {{phone}}</td>
    </tr>
    {{#customer_name}}
    <tr>
        <td colspan="3"> Покупець:</td>
    </tr>
    <tr>
        <td colspan="3"> {{customer_name}}</td>
    </tr>

    {{/customer_name}}

    <tr>
        <td colspan="3">Термiнал: {{pos_name}}</td>
    </tr>
    <tr>
        <td colspan="3">Касир: {{username}}</td>
    </tr>
    {{#_detail}}
    <tr>
        <td  colspan="3">{{tovar_name}}</td> 
    </tr>
    <tr>
        <td    align="right">{{quantity}}</td>
        <td    align="right">{{price}}</td>
        <td    align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">Всього:</td>
        <td align="right">{{total}}</td>
    </tr>

 
    {{#totaldisc}}
    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">Знижка:</td>
        <td align="right">{{totaldisc}}</td>
    </tr>
    {{/totaldisc}}
    {{#delbonus}}
    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">Списано бонусiв:</td>
        <td align="right">{{delbonus}}</td>
    </tr>
    {{/delbonus}}
   {{#prepaid}}
    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">Передплата:</td>
        <td align="right">{{prepaid}}</td>
    </tr>
    {{/prepaid}}
    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">До сплати:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">Решта:</td>
        <td align="right">{{exchange}}</td>
    </tr>
  
    <tr>
       <td colspan="3" > 
        {{#form1}}
          Форма оплати: готiвка
        {{/form1}}
        {{#form2}}
          Форма оплати: банкiвська карта
        {{/form2}}
        {{#form3}}
          Форма оплати: готiвка + карта
        {{/form3}}

       </td>
    </tr>  
    <tr>
       <td colspan="3" > 
        {{#trans}}
          № транзакції  {{trans}}
        {{/trans}}
       
       </td>
    </tr>       
   {{#addbonus}}
   <tr  >
        <td colspan="2" align="right">Нараховано бонусiв:</td>
        <td align="right">{{addbonus}}</td>
    </tr>
   {{/addbonus}}    
  {{#allbonus}}
   <tr  >
        <td colspan="2" align="right">Всього бонусiв:</td>
        <td align="right">{{allbonus}}</td>
    </tr>
   {{/allbonus}}          
     
  
   
   {{#checkslogan}}   
    <tr style="font-weight: bolder;">
        <td colspan="3"><br>{{checkslogan}}</td>

    </tr>
   {{/checkslogan}}   
   {{#promo}}   
    <tr style="font-weight: bolder;">
        <td colspan="3">{{promo}}</td>

    </tr>
   {{/promo}}   
    
    
    
{{#isdocqrcode}}                
       <tr>                    
                        <td colspan="3"> 
                            {{{docqrcode}}}
                        </td>

                    </tr>    
{{/isdocqrcode}}                    
</table>