<table class="ctable" border="0" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="7">
            Комісійні товари  на {{dt}}
        </td>
    </tr>
    
    {{#iscust}}
    <tr >
        <td  colspan="7">
          <b> Комітент:</b>  {{cust}}
            <br>
            <br>
        </td>
    </tr>
    {{/iscust}}
    
    <tr style="font-weight: bolder;">

         
        <th style="border: solid black 1px">Товар</th>
        <th style="border: solid black 1px">Код</th>
        <th style="border: solid black 1px">Ціна</th>
        <th align="right" style="border: solid black 1px">Придбано</th>
        <th align="right" style="border: solid black 1px">Продано</th>
        <th align="right" style="border: solid black 1px">Повернуто</th>
        <th align="right" style="border: solid black 1px">Накладна</th>

    </tr>
    {{#_detail}}
    <tr>

   
        <td>{{itemname}}</td>
        <td>{{item_code}}</td>

        <td align="right">{{price}}</td>
        <td align="right">{{buyqty}}</td>
        <td align="right">{{sellqty}}</td>
        <td align="right">{{retqty}}</td>
        <td align="right">{{docs}}</td>

    </tr>
    {{/_detail}}
 
    <tr >
        <td  colspan="7">
          <b> Продажі:</b>  
            <br>
           
        </td>
    </tr>
   {{#_detail2}}
     <tr>
        <td  >Дата {{dt}}</td>
        <td colspan="6">Документи {{docs}}</td>
    </tr>
      {{#items}}
     <tr>
        <td > </td>
        <td > {{itemname}}</td>
        <td align="right"> {{sm}}</td>
        <td colspan="4"> </td>
    </tr>
     {{/items}}
     <tr>
        <td colspan="7">Сума {{sm}}</td>
    </tr>

   {{/_detail2}}   
</table>
<br> <br>

