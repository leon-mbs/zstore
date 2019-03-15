 


<table class="ctable" cellspacing="0" cellpadding="1">
    <tr colspan="4" style="font-weight: bolder;">
        <td colspan="4">
            <h3 style="font-size: 16px;">АВС анализ '{{type}}' c {{from}} по {{to}}</h3>
        </td>
    </tr>
    <tr style="font-weight: bolder;">

        <th width="300px" style="border-bottom:1px #000 solid;">Название</th>
        <th width="100px" style="border-bottom:1px #000 solid;">Знач., тыс.</th>
        <th width="80px" style="border-bottom:1px #000 solid;">%</th>
        <th width="30px" style="border-bottom:1px #000 solid;"></th>
    </tr>
    {{#_detail}}
    <tr>

        <td width="300px" style="background-color: {{color}} ;">{{name}}</td>
        <td width="100px" style="background-color: {{color}} ;" align="right">{{value}} &nbsp;</td>
        <td width="80px" style="background-color: {{color}} ;" align="right">{{perc}} &nbsp;</td>
        <td width="30px" style="background-color: {{color}} ;">{{group}}</td>

    </tr>
    {{/_detail}}


</table>


