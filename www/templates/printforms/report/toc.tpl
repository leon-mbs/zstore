<table class="ctable" cellspacing="0" cellpadding="1">
    <tr    >
        <td align="center" colspan="3">
            <h4  >Обмеження системи </h4>
        </td>
    </tr>
 
    {{#isdetail1}}
  <tr    >
        <td  colspan="3"  >
            <b  >Актуальність складів  </b>   
        </td>
    </tr>    
 <tr  >
        <td  colspan="3"  >
            <small  > Товари, яких не виявилось на складі на момент замовлення </small>   
        </td>
    </tr>  
   <tr>
       
        <th   style="border: solid black 1px"  >Товар </th>
        <th   style="border: solid black 1px"  >Код </th>
        <th   style="border: solid black 1px" align="right">На суму </th>

    </tr>      
    {{#_detail1}}
    <tr>
       
        <td    >{{ name}} </td>
        <td    >{{code}} </td>
        <td    align="right">{{amount}} </td>

    </tr>
    {{/_detail1}}
    
    {{/isdetail1}}
 
 
    {{#isdetail2}}
  <tr    >
        <td  colspan="3"  >
           <br> <b  >Затримка відправки   </b>   
        </td>
    </tr>    
 <tr  >
        <td  colspan="3"  >
            <small  >Середня затримка  відправки товару по замовленню. <br>Для замовлень з різницею між датою замовлення і  відправкою  більше двох днів   </small>   
        </td>
    </tr>  
   <tr>
       
        <th   style="border: solid black 1px"  >Товар </th>
        <th   style="border: solid black 1px"  >Код </th>
        <th   style="border: solid black 1px" align="right">Дні </th>

    </tr>      
    {{#_detail2}}
    <tr>
       
        <td    >{{ name}} </td>
        <td    >{{code}} </td>
        <td    align="right">{{days}} </td>

    </tr>
    {{/_detail2}}
    
    {{/isdetail2}}
</table>


