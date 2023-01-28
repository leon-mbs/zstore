<table class="ctable" cellspacing="0" cellpadding="1">
      <tr  style="font-weight: bolder;">
        <td align="center" colspan="{{cols}}">
            <h3 style="font-size: 16px;">OLAP аналіз '{{type}}' з {{from}} по {{to}}</h3>
        </td>
    </tr>
    <tr>

        <td align="center" colspan="{{cols}}">
            Період з {{from}} по {{to}} <br> <br>
        </td>
    </tr>    
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


