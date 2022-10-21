<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="4" align="center">
            <b>Перемiщення ТМЦ № {{document_number}} від {{date}}</b> <br>
        </td>
    </tr>
    <tr>
        <td colspan="4">
            <b> З складу:</b> {{from}} на {{to}}
        </td>

    </tr>

     <tr>
        <td colspan="4">{{{notes}}}</td>
    </tr>

    <tr style="font-weight: bolder;">

        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Назва</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"></th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>


        <th align="right" width="50px" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Кіл.</th>

    </tr>
    {{#_detail}}
    <tr>

        <td>{{item_name}}</td>

        <td align="right">{{snumber}}</td>
        <td>{{msr}}</td>
        <td align="right">{{quantity}}</td>

    </tr>
    {{/_detail}}
   
</table>



