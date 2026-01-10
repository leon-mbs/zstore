<table class="ctable" border="0"   cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="5">
           Акцизнi марки   
        </td>
    </tr>
    <tr>

        <td align="center" colspan="5">
            Період з {{datefrom}} по {{dateto}}
        </td>
    </tr>
     {{#itemname}}
     <tr>

        <td   colspan="5">
         Товар:  {{itemname}}
        </td>
    </tr>
     {{/itemname}}
      <tr>

        <td   colspan="5">
         Оборот: <b> {{totamount}}</b>  &nbsp; Акциз: <b> {{amount}}</b>
        </td>
    </tr>    
    <tr style="font-weight: bolder;">

        <th style="border: solid black 1px">Марка</th>

        <th style="border: solid black 1px">Товар</th>
        <th style="border: solid black 1px">Артикул</th>
        <th style="border: solid black 1px">Документ</th>
        <th style="border: solid black 1px">Дата</th>

       
    </tr>
    {{#_detail}}
    <tr>


        <td>{{stamp}}</td>
        <td>{{itemname}}</td>
        <td>{{item_code}}</td>
        <td>{{document_number}}</td>
        <td>{{document_date}}</td>

     
 
    </tr>
    {{/_detail}}
  
</table>


