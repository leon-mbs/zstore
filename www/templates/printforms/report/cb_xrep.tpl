<table class="ctable" border="0" cellpadding="1" cellspacing="0" >
    <tr>
        <td align="center" colspan="2"> <h4>Х-Звіт </h4></td>
    </tr>
    <tr>

        <td  > Створено</td> <td  > {{created_at}}</td>
        
    </tr>   
   <tr>
        <td  > <b>Реалізація</b></td>        <td  >  </td>
    </tr>    
    <tr>
        <td  > Чеків  </td>        <td  align="right"> {{cnt}}</td>
    </tr>    
   <tr>
        <td  > Оплати:</td>        <td  >  </td>
    </tr>  
     {{#pays}} 
    <tr>
        <td  >  {{label}}</td>        <td align="right" > {{sum}}</td>
    </tr>  
      {{/pays}}   
 
  <tr>
        <td  > <b>Поверненняz</b></td>        <td  >  </td>
    </tr>    
    <tr>
        <td  > Чеків  </td>        <td align="right" > {{rcnt}}</td>
    </tr>    
   <tr>
        <td  > Оплати:</td>        <td  >  </td>
    </tr>  
     {{#rpays}} 
    <tr>
        <td  >  {{label}}</td>        <td align="right" > {{sum}}</td>
    </tr>  
      {{/rpays}}   
 
 
 

</table>
<br>