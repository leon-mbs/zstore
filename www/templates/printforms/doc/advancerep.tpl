<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="6" align="center">
            <b> Авансовий звiт № {{document_number}} від {{date}}</b> <br>
        </td>
    </tr>
    {{#isdetail}}
    <tr>
        <td><b>На склад:</b> </td> 
        <td colspan="5">
             {{to}}
        </td>
    </tr>

   {{#storeemp}}
    <tr>
         <td><b>На спiвробiтника:</b> </td> 
       <td colspan="5">
             {{storeemp}}
        </td>
    </tr>
 
    {{/storeemp}}    
     {{/isdetail}} 
    <tr>
          <td><b>Вiд спiвробiтника:</b> </td> 
      <td colspan="5">
             {{emp}}
        </td>
    </tr>
    {{#examount}}
    <tr>
         <td><b>Сума повернення:</b> </td> 
       <td colspan="5">
             {{examount}}
        </td>
    </tr>
   {{/examount}}  
  {{#spentamount}}
    <tr>
         <td><b>Сума витрат:</b> </td> 
       <td colspan="5">
             {{spentamount}}
        </td>
    </tr>
   <tr>
         <td><b>Тип витрат:</b> </td> 
       <td colspan="5">
             {{spenttypename}}
        </td>
    </tr>
   {{/spentamount}}  

    {{#isdetail}}  
    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Найменування</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"></th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>


        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Кіл.</th>
        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Ціна</th>
        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Сума</th>

    </tr>
    {{#_detail}}
    <tr>

        <td>{{item_name}}</td>
        <td>{{item_code}}</td>

        <td align="right">{{snumber}}</td>
        <td>{{msr}}</td>
        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="6" align="right">На суму:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>
    
    {{/isdetail}}  
    
    
    <tr>
        <td colspan="6">
            {{{notes}}}
        </td>

    </tr>
</table>



