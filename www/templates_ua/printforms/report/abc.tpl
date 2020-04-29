<table class="ctable" cellspacing="0" cellpadding="1">
    <tr colspan="4" style="font-weight: bolder;">
        <td colspan="4">
            <h3 style="font-size: 16px;">АВС аналіз '{{type}}' з {{from}} по {{to}}</h3>
        </td>
    </tr>
    <tr style="font-weight: bolder;">

        <th width="300px" style="border-bottom:1px #000 solid;">Назва</th>
        <th width="100px" style="border-bottom:1px #000 solid;" align="right">Знач., Тис.</th>
        <th width="80px" style="border-bottom:1px #000 solid;" align="right">%</th>
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
    <tr>

        <td width="300px" align="right"><b>Всього A:</b></td>
        <td width="100px" align="right"><b>{{totala}}</b> &nbsp;</td>
        <td width="80px" align="right"> &nbsp;</td>
        <td width="30px"></td>

    </tr>
    <tr>

        <td width="300px" align="right"><b>Всього B:</b></td>
        <td width="100px" align="right"><b>{{totalb}}</b> &nbsp;</td>
        <td width="80px" align="right"> &nbsp;</td>
        <td width="30px"></td>

    </tr>
    <tr>

        <td width="300px" align="right"><b>Всього C:</b></td>
        <td width="100px" align="right"><b>{{totalc}}</b> &nbsp;</td>
        <td width="80px" align="right"> &nbsp;</td>
        <td width="30px"></td>

    </tr>
    <tr>

        <td width="300px" align="right"><b>Всього:</b></td>
        <td width="100px" align="right"><b>{{total}}</b> &nbsp;</td>
        <td width="80px" align="right"> &nbsp;</td>
        <td width="30px"></td>

    </tr>

</table>


