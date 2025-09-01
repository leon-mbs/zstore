<table class="ctable" border="0" cellpadding="2" cellspacing="0">
    <tr>
        <td colspan="6">
            Замовник: {{customer_name}}
        </td>


    </tr>
    {{#isfirm}}
    <tr>
        <td colspan="7">
            Виконавець: {{firm_name}}
        </td>

    </tr>
    {{/isfirm}}
    {{#iscontract}}
    <tr>
        <td colspan="7">
            Договір: {{contract}} вiд {{createdon}}
        </td>

    </tr>
    {{/iscontract}}

    {{#isdevice}}
    <tr>
        <td colspan="7">
           Прийнято від замовника: {{device}} с/н: {{devsn}}
        </td>

    </tr>
    {{/isdevice}}
    <tr style="font-weight: bolder;">
        <td colspan="7" align="center">
           
          {{#isfinished}}  Акт виконаних робіт {{/isfinished}} 
          {{^isfinished}}  Квитанція до {{/isfinished}} 
           № {{document_number}} від {{date}}
        </td>
    </tr>

</table>
<br>
<table class="ctable" width="600" cellspacing="0" cellpadding="1" border="0">
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;">Найменування</th>
        <th style="border: 1px solid black;"> </th>
        <th style="border: 1px solid black;"  >Од.</th>
        <th style="border: 1px solid black;" align="right">Кількість</th>
        <th style="border: 1px solid black;" align="right">Вартість</th>
        <th style="border: 1px solid black;" align="right">Сума</th>

    </tr>
    {{#_detail}}
    <tr>
        <td>{{no}}</td>
        <td>{{service_name}}</td>

        <td>{{desc}}</td>

        <td  >{{msr}}</td>
        <td align="right">{{qty}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td colspan="6" style="border-top: 1px solid black;" align="right">Всього:</td>
        <td style="border-top: 1px solid black;" align="right">{{total}}</td>
    </tr>
    {{#totaldisc}}
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">Знижка:</td>
        <td align="right">{{totaldisc}}</td>
    </tr>
    {{/totaldisc}}    
   {{#bonus}}
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">Списані бонуси:</td>
        <td align="right">{{bonus}}</td>
    </tr>
    {{/bonus}}    
   {{#payamount}}
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">До сплати:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    {{/payamount}} 
   {{#payed}}  
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
     {{/payed}}  
    <tr>
        <td colspan="7"><br>
            Гарантія: {{gar}}
        </td>
    </tr>
    <tr>
        <td colspan="7"  ><small>{{{devdesc}}}</small></td>
    </tr>
    <tr>
        <td colspan="7"><small>{{{notes}}}</small></td>
    </tr>
       {{#payamount}}
   
   {{#totalstr}}
    <tr>
        <td colspan="7">До сплати <b>{{totalstr}}</b></td>
   </tr>
   {{/totalstr}}                    

              {{/payamount}} 
                    <tr>
                        <td colspan="3" > 
                            Виконавець ___________
                        </td>
                        <td colspan="2"> 
                            Замовник ___________
                        </td>

                    </tr>
                    <tr>
                        <td> </td>
                        <td colspan="4">
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


