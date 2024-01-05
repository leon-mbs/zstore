<table class="ctable"    >

    <tr>
        <td align="center" colspan="5">
            <h4> Фiскалiзованi чеки </h4>
        </td>
    </tr>
    <tr>

        <td colspan="5">
            Період з <b>{{datefrom}}</b> по <b>{{dateto}}</b> <br>
        </td>
    </tr>
   

    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px">Дата</th>

        <th style="border: solid black 1px">Номер</th>
        <th style="border: solid black 1px">ФН</th>
        <th style="border: solid black 1px">Тип</th>
        <th align="right" style="border: solid black 1px">Сума</th>
  
        {{#detail}}
    <tr>


        <td>{{docdata}}</td>

        <td  >{{docnumber}}</td>
        <td  >{{fn}}</td>
        <td  >{{type}}</td>
        <td align="right">{{amount}}</td>

       
       

    </tr>
    {{/detail}}

     <tr  >


        <td colspan="5" style="border: solid black 1px">
           Чекiв <b>{{cnt}}</b> на  суму <b>{{tam}}</b>. Повернень  <b>{{rcnt}}</b> на суму <b>{{rtam}}</b>. 
        </td>
      
       
    <tr> 
 

</table>


