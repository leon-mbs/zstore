<table class="ctable" border="0" cellpadding="1" cellspacing="0" {{{printw}}}>
    <tr>
        <td colspan="2">
          {{#isfinished}}  Акт виконаних робіт {{/isfinished}} 
          {{^isfinished}}  Квитанція до {{/isfinished}} 
        
        
         {{document_number}}</td>
    </tr>
    <tr>

        <td colspan="2">від {{date}}</td>
    </tr>
    <tr>

        <td colspan="2"> {{firmname}}</td>
    </tr>
    {{#shopname}}
    <tr>
        <td colspan="2"> {{shopname}}</td>
    </tr>
    {{/shopname}}
    <tr>

        <td colspan="2"> {{address}}</td>
    </tr>
    <tr>
        <td colspan="2"> {{phone}}</td>
    </tr>

    <tr>
        <td colspan="2"> {{customer_name}}</td>
    </tr>


    {{#isdevice}}
    <tr>
        <td colspan="2"> Прийнято від клієнта</td>
    </tr>
    <tr>
        <td colspan="2"> {{device}}</td>
    </tr>
    <tr>
        <td colspan="2"> с/н {{serial}}</td>
    </tr>
    {{/isdevice}}

    {{#iswork}}

    <tr>
        <td colspan="2">Роботи:</td>
    </tr>
    {{#slist}}
    <tr>
        <td>{{service_name}}</td>
        <td align="right">{{amount}}</td>
    </tr>

    {{/slist}}
     {{/iswork}}  
  
    {{#isitems}}
   <tr>
        <td colspan="2">Комплектуючi:</td>
    </tr>
    {{#ilist}}
    <tr>
        <td>{{itemname}}</td>
        <td align="right">{{amount}}</td>
    </tr>

    {{/ilist}}
    {{/isitems}}
   
    {{#istotal}}
    <tr>
        <td align="right">Всього:</td>
        <td align="right">{{total}}</td>
    </tr>
    {{/istotal}}

 


    {{#ispay}}
    <tr>
        <td colspan="2">Оплати:</td>
    </tr>
    {{#plist}}
    <tr>
        <td>{{pdate}}</td>
        <td align="right">{{ppay}}</td>
    </tr>

    {{/plist}}
    {{/ispay}}
    <tr>
        <td colspan="2"> {{gar}}</td>

    </tr>
    <tr>
        <td colspan="2"> <br></td>

    </tr>
    <tr>
        <td>Виконавець</td>
        <td>________</td>
    </tr>
    <tr>
        <td colspan="2"> <br></td>

    </tr>
    <tr>
        <td>Клієнт</td>
        <td>________</td>
    </tr>
</table>