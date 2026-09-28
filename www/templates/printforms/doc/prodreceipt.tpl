<table class="ctable" border="0" cellspacing="0" cellpadding="2">

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="8" valign="middle">
            Оприбуткування з виробництва № {{document_number}} від {{date}} <br>
        </td>
    </tr>
    <tr>
        <td colspan="3">Виробнича ділянка</td><td colspan="5" valign="middle"><b>{{pareaname}}</b></td>
    </tr>
   <tr>
        <td colspan="3">На склад</td><td colspan="5" valign="middle"><b>{{storename}}</b></td>
    </tr>
    {{#emp}}
    <tr>
        <td colspan="3"><b>Виконавець:</b></td><td colspan="3">{{emp}}</td>
    </tr>
 
    {{/emp}}      
    <tr>
        <td colspan="8" valign="middle">
            {{{notes}}}<br>
        </td>
    </tr>
    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30pt">№</th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Найменування</th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>

        <th align="right" style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="50pt">Кіл.</th>
        <th align="right" style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60pt">Ціна</th>
        <th align="right" style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80pt">Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td colspan="2">{{itemname}}</td>
        <td colspan="2" data-type="s">{{itemcode}}</td>
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

