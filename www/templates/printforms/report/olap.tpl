<table class="ctable" cellspacing="0" cellpadding="1">
 
    <tr><th> </th>
         {{#hor}}
         <th style=" text-align:left"> {{name}}</th>
         {{/hor}}
         
    </tr>
 
 {{#ver}}
  <tr>
     <th style=" text-align:left"> {{name}}</th>
     {{#row}}
       <td style=" text-align:right">{{val}} </td>
     {{/row}}
  </tr>

 {{/ver}}
 
 
</table>


