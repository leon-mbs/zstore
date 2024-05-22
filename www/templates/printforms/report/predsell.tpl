<table class="ctable" cellspacing="0" cellpadding="1">
    <tr   style="font-weight: bolder;">
        <td colspan="4">
            <h3 style="font-size: 16px;">Прогноз  продаж </h3>
        </td>
    </tr>
    <tr style="font-weight: bolder;">

        <th   style="border-bottom:1px #000 solid;">Товар</th>
        <th   style="border-bottom:1px #000 solid;" align="right">Прогноз </th>
        <th   style="border-bottom:1px #000 solid;" align="right">На складі</th>
        <th   style="border-bottom:1px #000 solid;" align="right">Закупити</th>

    </tr>
    {{#_detail}}
    <tr>

        <td   style="background-color: {{color}} ;">{{name}}</td>
        <td   style="background-color: {{color}} ;" align="right">{{qty}} </td>
        <td   style="background-color: {{color}} ;" align="right">{{onstore}} </td>
        <td   style="background-color: {{color}} ;" align="right">{{tobay}} </td>


    </tr>
    {{/_detail}}
 

</table>


