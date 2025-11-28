<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="3" align="center">
            <b> Ручна проводка № {{document_number}} від {{date}}</b> <br>
        </td>
    </tr>
    <tr>
        <td colspan="3"  >
             {{notes}}
        </td>
    </tr>    
  <tr style="font-weight: bolder;">
      
        <th    style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Дебет</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Кредит</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Сума</th>
    
     
    </tr>
    {{#_detail}}
    <tr>
 
        <td  >{{dt}}</td>
        <td  >{{ct}}</td>
     
        <td align="right"  >{{amount}}</td>
   
    
    </tr>
    {{/_detail}}


</table>


