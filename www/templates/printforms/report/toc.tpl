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
        <th   style="border: solid black 1px"  >Артикул </th>
        <th   style="border: solid black 1px" align="right">На суму </th>

    </tr>      
    {{#_detail1}}
    <tr>
       
        <td    >{{name}} </td>
        <td    >{{code}} </td>
        <td    align="right">{{amount}} </td>

    </tr>
    {{/_detail1}}
    
    {{/isdetail1}}
 
 
    {{#isdetail2}}
  <tr    >
        <td  colspan="3"  >
           <br> <b  >Затримка відправки  товару по замовленню</b>   
        </td>
    </tr>    
 <tr  >
        <td  colspan="3"  >
            <small  >   Для замовлень з різницею між датою замовлення і  
            відправкою  більше двох днів (сер.значення)  </small>   
        </td>
    </tr>  
   <tr>
       
        <th   style="border: solid black 1px"  >Товар </th>
        <th   style="border: solid black 1px"  >Артикул </th>
        <th   style="border: solid black 1px" align="right">Дні </th>

    </tr>      
    {{#_detail2}}
    <tr>
       
        <td    >{{name}} </td>
        <td    >{{code}} </td>
        <td    align="right">{{days}} </td>

    </tr>
    {{/_detail2}}
    
    {{/isdetail2}}
    
    
  {{#isdetail3}}
  <tr    >
        <td  colspan="3"  >
           <br> <b  >Затримка поставок  ТМЦ </b>   
        </td>
    </tr>    
 <tr  >
        <td  colspan="3"  >
        
            <small    >  Для заявок з різницею між датою створення і  
                  створенням вхідного документу більше    двох днів (сер.значення)       
         </small>   
        </td>
    </tr>  
   <tr>
       
        <th   style="border: solid black 1px"  >Товар </th>
        <th   style="border: solid black 1px"  >Артикул </th>
        <th   style="border: solid black 1px" align="right">Дні </th>

    </tr>      
    {{#_detail3}}
    <tr>
       
        <td    >{{name}} </td>
        <td    >{{code}} </td>
        <td    align="right">{{days}} </td>

    </tr>
    {{/_detail3}}
    
    {{/isdetail3}}    
   
   
  {{#isdetail4}}
  <tr    >
        <td  colspan="3"  >
           <br> <b  >Затримка поставок ТМЦ після оплати   </b>   
        </td>
    </tr>    
 <tr  >
        <td  colspan="3"  >
            <small  >  Затримка  створення прибуткової накладної  більше двох днів псля оплати вхідного рахунку  (сер.значення)  </small>   
        </td>
    </tr>  
   <tr>
       
        <th colspan="2"  style="border: solid black 1px"  >Постачальник </th>
     
        <th   style="border: solid black 1px" align="right">Дні </th>

    </tr>      
    {{#_detail4}}
    <tr>
       
        <td colspan="2"   >{{name}} </td>
       
        <td    align="right">{{days}} </td>

    </tr>
    {{/_detail4}}
    
    {{/isdetail4}}    
       
   {{#isdetail5}}
  <tr    >
        <td  colspan="3"  >
           <br> <b  >Неліквідні товари  </b>   
        </td>
    </tr>    
 <tr  >
        <td  colspan="3"  >
            <small  > Товари на складах, які не мали жодного продажу </small>   
        </td>
    </tr>  
   <tr>
       
        <th   style="border: solid black 1px"  >Товар </th>
        <th   style="border: solid black 1px"  >Артикул </th>
        <th   style="border: solid black 1px" align="right">На суму </th>

    </tr>      
    {{#_detail5}}
    <tr>
       
        <td    >{{itemname}} </td>
        <td    >{{item_code}} </td>
        <td    align="right">{{amount}} </td>

    </tr>
    {{/_detail5}}
    
    {{/isdetail5}}
   
    
</table>


