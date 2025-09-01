<table class="ctable" border="0"   cellpadding="2" cellspacing="0">


    <tr>

        <td align="center" colspan="3">
          <b>     Період з {{datefrom}} по {{dateto}}  </b> 
        </td>
      </tr>
      <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Оплата по виробництву
        </td>
    </tr>
        {{#isemp}}  
     <tr>   <td   colspan="3">
          <b>     {{emp_name}}   </b> 
        </td>    </tr>
        
         {{#_detail}}
    <tr>
        <td  >{{document_date}}</td>
        <td  >{{document_number}}</td>

        <td align="right">{{amount}}</td>


    </tr>
    {{/_detail}}       
          
        {{/isemp}}  
        
        
        {{^isemp}}  
        
        
        {{#_detail}}
    <tr>
        <td colspan="2">{{name}}</td>

        <td align="right">{{amount}}</td>


    </tr>
    {{/_detail}}

   {{/isemp}}  
  
  
     <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Виконанi роботи
        </td>
    </tr> 
     
        {{#_detail2}}
    <tr>
        <td  >{{name}}</td>

        <td align="right">{{qty}}</td>
        <td align="right">{{amount}}</td>
      <td  > </td>

    </tr>
    {{/_detail2}}  
  
  
</table>


<br> 

