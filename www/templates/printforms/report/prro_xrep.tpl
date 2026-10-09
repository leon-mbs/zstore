<table class="ctable" border="0" cellpadding="1" cellspacing="0" >
   
    <tr>
        <td  > <b>ФОП</b></td>        <td colspan="2"  > {{firmname}} </td>         
    </tr> 
   <tr>
        <td  > <b>ЄДРПОУ</b></td>        <td colspan="2" > {{tin}} </td>        
    </tr> 
    <tr>  
        <td  > <b>Торгова точка</b></td>        <td colspan="2"  >{{pointname}}  </td>     
    </tr>   
   <tr>  
        <td  > <b>Адреса</b></td>        <td colspan="2"  >  {{address}}</td>       
    </tr>   
  <tr>  
        <td  > <b>Термінал</b></td>        <td colspan="2"  >{{posnumber}}  </td>    
    </tr>   
   
    <tr>
        <td align="center" colspan="3"> <h4>Х-Звіт </h4></td>
    </tr>
    <tr>

        <td  > Дата</td> <td colspan="2" > {{created_at}}</td>      
        
    </tr>    
   <tr>

        <td  > <td colspan="3" > &nbsp;</td>      
        
    </tr>    
      {{#sin}} 
    <tr>
        <td  > Сдужбове  внесення</td>        <td   align="right" > {{sin}}</td>  <td  >  </td>   
    </tr>  
      {{/sin}}   
     {{#sout}} 
    <tr>
        <td  > Службова  видача</td>        <td   align="right" > {{sout}}</td>  <td  >  </td>   
    </tr>  
      {{/sout}}                                                  
   <tr>
        <td  > <b>Реалізація</b></td>        <td  >  </td>        <td  >  </td>  
    </tr>    
   <tr>
        <td  > Чеків</td>        <td style="width:100px" align="right"  > {{cnt}}</td>   <td  >  </td>   
    </tr>    
    <tr>
        <td  > Оплати:</td>        <td  >  </td>    <td  >  </td>   
    </tr>  
     {{#pays}} 
    <tr>
        <td  >  {{label}}</td>        <td  align="right"  > {{sum}}</td>   <td  >  </td>   
    </tr>  
      {{/pays}}   
  <tr>
        <td  > <b>Повернення</b></td>        <td  >  </td>    <td  >  </td>   
    </tr>    
   <tr>
        <td  > Чеків</td>        <td  align="right" > {{rcnt}}</td>     <td  >  </td>   
    </tr>    
    <tr>
        <td  > Оплати:</td>        <td  >  </td>     <td  >  </td>   
    </tr>  
     {{#rpays}} 
    <tr>
        <td  >  {{label}}</td>        <td   align="right" > {{sum}}</td>  <td  >  </td>   
    </tr>  
      {{/rpays}}   
 
  


</table>
<br>