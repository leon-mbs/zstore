<table class="ctable" border="0" cellpadding="1" cellspacing="0"  {{{style}}}}>
    <tr style="font-weight: bolder;">
        <td colspan="3">Чек {{document_number}}</td>
    </tr>
    {{#fiscalnumber}}
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

        <td colspan="2"> {{firm_name}}</td>
    </tr>
    <tr>

        <td colspan="3">ІПН {{inn}}</td>
    </tr>
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
        <td colspan="3">Касир:</td>
    </tr>
    <tr>
        <td colspan="3"> {{username}}</td>
    </tr>
    {{#_detail}}
    <tr>
        <td colspan="3">{{tovar_name}}</td>
        <td valign="top"  align="right">{{quantity}}</td>
        <td valign="top"  align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">Всього:</td>
        <td align="right">{{total}}</td>
    </tr>

    {{^prepaid}}
    {{#isdisc}}
    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">Знижка:</td>
        <td align="right">{{paydisc}}</td>
    </tr>
    {{/isdisc}}
    <tr style="font-weight: bolder;">
        <td colspan="2" align="right">До оплати:</td>
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
    {{/prepaid}}
    <tr style="font-weight: bolder;">
        <td colspan="3"><br>{{checkslogan}}</td>

    </tr>
    <tr>
                        <td colspan="3" > 
                            {{{docbarcode}}}
                        </td>
       </tr>                 
       <tr>                    
                        <td colspan="3"> 
                            {{{docqrcode}}}
                        </td>

                    </tr>    
</table>