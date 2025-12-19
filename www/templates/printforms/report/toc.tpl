<table class="ctable" cellspacing="0" cellpadding="1">
    <tr    >
        <td align="center" colspan="5">
            <h4  >Прогноз  продаж </h4>
        </td>
    </tr>
    <tr style="font-weight: bolder;">

        <th   style="border-bottom:1px #000 solid;">Товар</th>
        <th   style="border-bottom:1px #000 solid;">Артикул</th>
        <th   style="border-bottom:1px #000 solid;" align="right">Прогноз </th>
        <th   style="border-bottom:1px #000 solid;" align="right">На складі</th>
        <th   style="border-bottom:1px #000 solid;" align="right">
        
       {{#tovar}}
        Закупити
       {{/tovar}}
       {{^tovar}}
       Виробити
       {{/tovar}}
        
        </th>

    </tr>
    {{#_detail}}
    <tr>

        <td   style="background-color: {{color}} ;">{{itemname}}</td>
        <td   style="background-color: {{color}} ;">{{item_code}}</td>
        <td   style="background-color: {{color}} ;" align="right">{{qty}} </td>
        <td   style="background-color: {{color}} ;" align="right">{{onstore}} </td>

        <td   style="background-color: {{color}} ;" align="right">{{tobay}} </td>


    </tr>
    {{/_detail}}
 

</table>


