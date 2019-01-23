 
<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr  >
        <td colspan="4" align="center">
           <b> Перемещение ТМЦ № {{document_number}} от {{date}}</b> <br>
        </td>
    </tr>
    <tr>
        <td colspan="4">
           <b> Со склада:</b> {{from}}
        </td>
     
      </tr>
      <tr>
        <td colspan="4">
            <b>На склад:</b> {{to}}
        </td>
    </tr>
   

 
    <tr style="font-weight: bolder;">
        <th width="20px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">№</th>
        <th  style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Название</th>
         
        
        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Кол.</th>
        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Цена</th>
    </tr>
    {{#_detail}}
    <tr>
        <td>{{no}}</td>
        <td>{{item_name}}</td>

        
        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
    </tr>
    {{/_detail}}
</table>


