<table class="ctable" border="0" cellpadding="1" cellspacing="0" {{{printw}}}>
    
  
    <tr>
        <td colspan="3">Рахунок {{document_number}}</td>
    </tr>
    
    
 
 
    <tr>

        <td colspan="3">від {{time}}</td>
    </tr>
    <tr>

        <td colspan="3"> {{firm_name}}</td>
    </tr>
 
 
 

    <tr>

        <td colspan="3"> {{address}}</td>
    </tr>
 
    {{#customer_name}}
    <tr>
        <td colspan="3"> Покупець:</td>
    </tr>
    <tr>
        <td colspan="3"> {{customer_name}}</td>
    </tr>

    {{/customer_name}}

 


    {{#_detail}}
    <tr>
        <td colspan="3">{{tovar_name}}</td>

    </tr>


    <tr>

        <td colspan="2" align="right">{{quantity}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    
    {{/_detail}}
    <tr>
        <td colspan="2" align="right">Всього:</td>
        <td align="right">{{total}}</td>
    </tr>

   
    {{#isdisc}}
    <tr  >
        <td colspan="2" align="right">Знижка:</td>
        <td align="right">{{totaldisc}}</td>
    </tr>
    {{/isdisc}}
   {{#bonus}}
    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">Списано бонусiв::</td>
        <td align="right">{{bonus}}</td>
    </tr>
    {{/bonus}}

    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">До сплати:</td>
        <td style="font-size:larger" align="right">{{payamount}}</td>
    </tr>
    
                      
                    
</table>