<table class="ctable" border="0"   cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="9">
           Форма ведення обліку товарних запасів
        </td>
    </tr>
    <tr style="font-size:larger;  ">
        <td align="center" colspan="9">
           {{firmname}}
        </td>
    </tr>
 
 

    <tr  >

        <th rowspan="2" style="border: solid black 1px">№</th>

        <th rowspan="2" style="border: solid black 1px">Дата внесення запису</th>
        <th colspan="6" style="border: solid black 1px">Реквізити первинного документа, що підтверджує надходження або вибуття товару </th>

        <th rowspan="2"   style="border: solid black 1px">Примітки  </th>
        
    </tr>
    <tr> 
    <th   style="border: solid black 1px">Вид</th>
    <th   style="border: solid black 1px">Дата</th>
    <th   style="border: solid black 1px">№</th>
    <th   style="border: solid black 1px">Постачальник (продавець, виробник) або отримувач товару</th>
    <th   style="border: solid black 1px">Надходження товару (придбання, повернення товару від покупця, або внутрішнє переміщення)</th>
    <th   style="border: solid black 1px">Вибуття товару (продаж товарів в безготівковій формі, внутрішнє переміщення, знищення або втрата, повернення товару постачальнику, використання на власні потреби)
 
</th>
    
    
    </tr>
    
    {{#_detail}}
    <tr>

        <td>{{nrec}}</td>

        <td>{{datedec}}</td>
        <td>{{doctype}}</td>

        <td  >{{docdate}}</td>
        <td  >{{docno}}</td>
        <td  >{{cust}}</td>
        <td align="right">{{in}}</td>
        <td align="right">{{out}}</td>
        <td  > </td>
 
    </tr>
    {{/_detail}}
  
   
   
</table>


