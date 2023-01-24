<table class="ctable" cellspacing="0" cellpadding="1">
    <tr colspan="4" style="font-weight: bolder;">
        <td colspan="4">
            <h3 style="font-size: 16px;">АВС аналіз '{{type}}' з {{from}} по {{to}}</h3>
        </td>
    </tr>
    <tr style="font-weight: bolder;">

        <th   style="border-bottom:1px #000 solid;">Назва</th>
        <th   style="border-bottom:1px #000 solid;" align="right">Знач. </th>
        <th   style="border-bottom:1px #000 solid;" align="right">%</th>
        <th   style="border-bottom:1px #000 solid;"></th>
    </tr>
    {{#_detail}}
    <tr>

        <td   style="background-color: {{color}} ;">{{name}}</td>
        <td   style="background-color: {{color}} ;" align="right">{{value}} &nbsp;</td>
        <td   style="background-color: {{color}} ;" align="right">{{perc}} &nbsp;</td>
        <td   style="background-color: {{color}} ;">{{group}}</td>

    </tr>
    {{/_detail}}
    <tr>

        <td   align="right"><b>Всього A:</b></td>
        <td   align="right"><b>{{totala}}</b> &nbsp;</td>
        <td   align="right"> &nbsp;</td>
        <td  ></td>

    </tr>
    <tr>

        <td   align="right"><b>Всього B:</b></td>
        <td   align="right"><b>{{totalb}}</b> &nbsp;</td>
        <td   align="right"> &nbsp;</td>
        <td  ></td>

    </tr>
    <tr>

        <td   align="right"><b>Всього C:</b></td>
        <td   align="right"><b>{{totalc}}</b> &nbsp;</td>
        <td   align="right"> &nbsp;</td>
        <td  ></td>

    </tr>
    <tr>

        <td   align="right"><b>Всього:</b></td>
        <td   align="right"><b>{{total}}</b> &nbsp;</td>
        <td   align="right"> &nbsp;</td>
        <td  ></td>

    </tr>

</table>


