<table class="ctable" border="0" cellspacing="0" cellpadding="2">

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="8" valign="middle">
            Оприбуткування з виробництва № {{document_number}} від {{date}} <br>
        </td>
    </tr>
    <tr>
        <td colspan="8" valign="middle">
            Виробнича ділянка <b>{{pareaname}}</b><br>
        </td>
    </tr>
   <tr>
        <td colspan="8" valign="middle">
            На склад <b>{{storename}}</b><br>
        </td>
    </tr>
    {{#emp}}
    <tr>
        <td colspan="6">
            <b>Виконавець:</b> {{emp}}
        </td>
    </tr>
 
    {{/emp}}      
    <tr>
        <td colspan="8" valign="middle">
            {{{notes}}}<br>
        </td>
    </tr>
    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Найменування</th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>

        <th align="right" style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="50">Кіл.</th>
        <th align="right" style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Ціна</th>
        <th align="right" style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td colspan="2">{{itemname}}</td>
        <td colspan="2">{{itemcode}}</td>
        <td>{{msr}}</td>

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="8" align="right">Разом:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>


</table>

