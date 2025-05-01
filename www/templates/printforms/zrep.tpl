 
   <table class="ctable" border="0" cellpadding="1" cellspacing="0"  }>
    <tr>
        <td colspan="2" style="font-weight:bolder;text-align:center" >Z-звiт</td>
    </tr>
   
   {{#test}}
   <tr>
        <td colspan="2" style=" text-align:center" >Тестовий режим</td>
    </tr>
   {{/test}}
    
    <tr>

        <td colspan="2"  > Дата  {{date}}</td>
    </tr>
    <tr>

        <td  colspan="2"> {{firm}} </td>
         
    </tr>
    <tr>

        <td  colspan="2"> {{{address}}} </td>
         
    </tr>
  <tr>

        <td  > ЄДРПОУ/IПН</td>
        <td  > {{inn}} </td>
    </tr>
    <tr>

        <td  > ФН терм.</td>
        <td  > {{fnpos}} </td>
    </tr>
    <tr>

        <td  > ФН док.</td>
        <td  > {{fndoc}} </td>
    </tr>

   <tr>

        <td  > <b>Продаж</b></td>
        <td  >  </td>
    </tr>
    {{#payments}}
      <tr>

        <td  >{{forma}}</td>
        <td style="text-align:right" > {{amount}} </td>
    </tr> 
    {{/payments}}
   <tr>

        <td  >Кiлькiсть чекiв</td>
        <td style="text-align:right" > {{cnt}} </td>
    </tr>
 
   <tr>

        <td  > <b>Повернення</b></td>
        <td  >  </td>
    </tr>
    {{#rpayments}}
      <tr>

        <td  >{{forma}}</td>
        <td style="text-align:right" > {{amount}} </td>
    </tr> 
    {{/rpayments}}    
   <tr>

        <td  >Кiлькiсть чекiв</td>
        <td  style="text-align:right" > {{rcnt}} </td>
    </tr>
 
    
    
       
    </table>
 