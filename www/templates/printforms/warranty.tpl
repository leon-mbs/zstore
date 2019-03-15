 
<table class="ctable" border="0" cellspacing="0" cellpadding="2">


    <tr style="font-weight: bolder;">
        <td></td>
        <td colspan="4">
            Гарантийный талон {{document_number}} 
        </td>
        <td colspan="3">
            от {{date}} 
        </td>
    </tr>
    <tr>
        <td colspan="8">&nbsp;</td>
    </tr>
    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th colspan="2" width="230" style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Наименование</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="50">Кол.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Цена</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="70">Сумма</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="140">Сер. номер</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="70">Гар., мес</th>
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
            <b> Покупатель</b> {{customer}} 
        </td>
        <td colspan="4">
            <b> Продавец</b> {{firmname}} 
        </td>

    </tr>
    <tr>
        <td></td>
        <td colspan="8">
            Подпись
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

