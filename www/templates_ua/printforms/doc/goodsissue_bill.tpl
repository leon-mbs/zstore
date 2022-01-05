<table class="ctable" border="0" cellpadding="1" cellspacing="0"  {{{style}}}}>
    <tr>
        <td colspan="3">Накладна №{{document_number}}</td>
    </tr>
    <tr>

        <td colspan="3">вiд {{date}}</td>
    </tr>
    <tr>
        <td colspan="3"> Продавець:</td>
    </tr>
    <tr>

        <td colspan="2"> {{firm_name}}</td>
    </tr>


    <tr>
        <td colspan="3"> Тел. {{phone}}</td>
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

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr>
        <td colspan="2" align="right">Всього:</td>
        <td align="right">{{total}}</td>
    </tr>
    <tr>
                        <td colspan="3" > 
                            {{{docbarcode}}}
                        </td>
       </tr>                 
   

</table>