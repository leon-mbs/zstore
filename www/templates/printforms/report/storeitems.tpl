<table class="ctable" border="0" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="{{$colspan}}">
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
    </tr>
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
   
</table>


