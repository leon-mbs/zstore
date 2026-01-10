<table class="ctable" border="0" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="{{colspan}}">
            Стан складiв  на  {{date}} 
        </td>
    </tr>
 

    <tr style="font-weight: bolder;">

        <th style="border: solid black 1px">Найменування</th>
        <th style="border: solid black 1px">Артикул</th>
        <th style="border: solid black 1px">Бренд</th>
        <th style="border: solid black 1px">Miн. кiл.</th>
        {{#cfnames}}
        <th style="border: solid black 1px">{{value}} </th>
        {{/cfnames}}
        {{#storescol}}
        <th style="border: solid black 1px">{{value}} </th>
        {{/storescol}}
        {{#ver}}
        <th style="border: solid black 1px" align="right"> Кiл. </th>
        {{/ver}}
        
    </tr>
     {{^ver}}  
    {{#_detail}}       
       
    <tr>

        <td>{{itemname}}</td>
        <td>{{item_code}}</td>
        <td>{{brand}}</td>
        <td align="right">{{minqty}}</td>
        {{#cfcol}}
          <td align="right">{{val}}</td>
        {{/cfcol}}
        {{#stlistcol}}
          <td align="right">{{qty}}</td>
        {{/stlistcol}}
    </tr>
 
    
    {{/_detail}}
    {{/ver}}
    {{#ver}}   
     {{#_detail}}       
      <tr style="font-weight: bolder;">
        <td   colspan="{{colspan}}">
            {{storename}}
        </td>
      </tr>    
        {{#items}}
    <tr>

        <td>{{itemname}}</td>
        <td>{{item_code}}</td>
        <td>{{brand}}</td>
        <td align="right">{{minqty}}</td>
        {{#cfcol}}
          <td align="right">{{val}}</td>
        {{/cfcol}}
     <td align="right">{{qty}}</td>
    </tr>
 
    
    {{/items}}
  <tr  >
        <td   colspan="{{colspan}}">
               &nbsp;
        </td>
      </tr>      
    {{/_detail}}
    {{/ver}}   
</table>


