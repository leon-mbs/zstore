<table class="ctable" border="0" cellspacing="0" cellpadding="2">


    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="9" valign="middle">
            Списання на виробництво № {{document_number}} від {{date}} <br>
        </td>
    </tr>
    <tr>
        <td colspan="3">Виробнича ділянка</td><td colspan="6" valign="middle"><b>{{pareaname}}</b></td>
    </tr>
   <tr>
        <td colspan="3">Зі складу</td><td colspan="6" valign="middle"><b>{{storename}}</b></td>
    </tr>
    {{#emp}}
    <tr>
        <td colspan="3"><b>Відповідальний:</b></td><td colspan="6">{{emp}}</td>
    </tr>
 
    {{/emp}}       
    <tr>
        <td colspan="9" valign="middle">
            {{{notes}}}<br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30pt">№</th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Найменування
        </th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Од.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Ком.</th>

        <th align="right" style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60pt">Кіл.</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td colspan="2">{{tovar_name}}</td>
        <td colspan="2" data-type="s">{{tovar_code}}</td>
        <td>{{msr}}</td>
        <td>{{cell}}</td>

        <td align="right">{{quantity}}</td>

    </tr>
    {{/_detail}}


</table>

