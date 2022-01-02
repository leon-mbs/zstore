<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="4" align="center">
            <b> Инвентаризация № {{document_number}} от {{date}}</b> <br>
        </td>
    </tr>
    <tr>
        <td colspan="4">
            <b> Склад:</b> {{store}}
        </td>

    </tr>



    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Название</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"> </th>


        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Кол.</th>
        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Факт</th>
    </tr>
   
       {{#_detaillost}}
    <tr>

        <td>{{item_name}}</td>
        <td>{{snumber}}</td>


        <td align="right">{{quantity}}</td>
        <td align="right" style="color:red">{{qfact}}</td>
    </tr>
    {{/_detaillost}}
    {{#_detailover}}
    <tr>

        <td>{{item_name}}</td>
        <td>{{snumber}}</td>


        <td align="right">{{quantity}}</td>
        <td align="right"  >{{qfact}}</td>
    </tr>
    {{/_detailover}}
    {{#_detail}}
    <tr>

        <td>{{item_name}}</td>
        <td>{{snumber}}</td>


        <td align="right">{{quantity}}</td>
        <td align="right">{{qfact}}</td>
    </tr>
    {{/_detail}}
 
       {{#summinus}}  
   <tr>
        <td  >
            <b> Недостача на  сумму:   </b>
        </td>
      <td colspan="3" align="right" style="color:red">
            <b>  {{summinus}}   </b>
        </td>

    </tr>  
     {{/summinus}}   
   
   {{#sumplus}} 
   <tr>
        <td  >
           <b>  Излишки на  сумму:  </b>
        </td>    <td colspan="3" align="right" >
             <b>  {{sumplus}}   </b>
        </td>

    </tr>
       {{/sumplus}} 
  
</table>


