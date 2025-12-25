<table class="ctable" cellspacing="0" cellpadding="1">
    <tr    >
        <td align="center" colspan="5">
            <h4  >Обмеження системи </h4>
        </td>
    </tr>
 
    {{#isdetail1}}
  <tr    >
        <td  >
            <b  >Актуальність складів  </b>   
        </td>
    </tr>    
 <tr  >
        <td  >
            <small  > Товари, яких не виявилось на складі на момент замовлення </small>   
        </td>
    </tr>  
   <tr>
       
        <td   style="border: solid black 1px"  >Товар </td>
 
        <td   style="border: solid black 1px" align="right">На суму </td>

    </tr>      
    {{#_detail1}}
    <tr>
       
        <td    >{{item_name}} </td>
        <td    align="right">{{days}} </td>

    </tr>
    {{/_detail1}}
    
    {{/isdetail1}}
 

</table>


