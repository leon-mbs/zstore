<table class="ctable" border="0" cellspacing="0" cellpadding="2">


    <tr style="font-weight: bolder;">
        <td></td>
        <td colspan="4">
            Гарантійний талон {{document_number}}
        </td>
        <td colspan="3">
            від {{date}}
        </td>
    </tr>
   <tr>
        <td colspan="8">{{{notes}}}</td>
    </tr>
    
    <tr>
        <td colspan="8">&nbsp;</td>
    </tr>
    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th colspan="2" width="230" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Найменування</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Кіл.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Ціна</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Сума</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Сер. номер</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Гар., міс.</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{ no}}</td>
        <td colspan="2">{{tovar_name}}</td>
        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
        <td align="right">{{sn}}</td>
        <td align="right">{{warranty}}</td>
    </tr>
    {{/_detail}}

    <tr>
        <td colspan="8">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="8">&nbsp;</td>
    </tr>
    <tr>
        <td></td>
        <td colspan="3">
            <b> Покупець</b> {{customer_name}}
        </td>
        <td colspan="4">
            <b> Продавець</b> {{firm_name}}
        </td>

    </tr>
    <tr>
        <td></td>
        <td colspan="8">
            Підписи
        </td>


    </tr>
    <tr>
        <td></td>
        <td colspan="4">

        </td>
        <td colspan="3">
            М.П.
        </td>


    </tr>
</table>

