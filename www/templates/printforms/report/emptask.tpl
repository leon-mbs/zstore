<table class="ctable" border="0"   cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Оплата по виробництву
        </td>
    </tr>
    <tr>

        <td align="center" colspan="3">
          <b>     Період з {{datefrom}} по {{dateto}}  </b> 
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
  
</table>


<br> <br>

