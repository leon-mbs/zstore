<table class="ctable" border="0" cellspacing="0" cellpadding="2">


    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="8" valign="middle">
            Списання на виробництво № {{document_number}} від {{date}} <br>
        </td>
    </tr>
    <tr>
        <td colspan="8" valign="middle">
            Виробнича ділянка <b>{{pareaname}}</b><br>
        </td>
    </tr>
   <tr>
        <td colspan="8" valign="middle">
            Зi складу <b>{{storename}}</b><br>
        </td>
    </tr>
    <tr>
        <td colspan="8" valign="middle">
            {{{notes}}}<br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Найменування
        </th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Од.</th>

        <th align="right" style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Кіл.</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td colspan="2">{{tovar_name}}</td>
        <td colspan="2">{{tovar_code}}</td>
        <td>{{msr}}</td>

        <td align="right">{{quantity}}</td>

    </tr>
    {{/_detail}}


</table>

