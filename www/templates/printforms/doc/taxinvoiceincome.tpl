

<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <th width="30">&nbsp;</th>
        <th width="100">&nbsp;</th>
        <th width="130">&nbsp;</th>
        <th width="50">&nbsp;</th>
        <th width="50">&nbsp;</th>
        <th width="60">&nbsp;</th>
        <th width="80">&nbsp;</th>
    </tr>

    <tr>
        <td></td>
        <td>Поставшик</td>
        <td colspan="5">{{customername}}</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td colspan="5">{{code}}</td>
    </tr>
    <tr>
        <td></td>
        <td>Получатель</td>
        <td colspan="5">{{firmname}}</td>
    </tr>

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="7" valign="middle">
            <br><br> Входящая НН № {{document_number}} від {{date}} <br><br><br>
        </td>
    </tr>
</table>
<br>
<table class="ctable" width="600" cellspacing="0" cellpadding="1" border="0">
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;" width="180">Наименование</th>
        <th style="border: 1px solid black;" width="50">Ед.изм</th>
        <th style="border: 1px solid black;" width="50">Кол.</th>
        <th style="border: 1px solid black;" width="50">Цена-</th>
        <th style="border: 1px solid black;" width="50">Цена+</th>
        <th style="border: 1px solid black;" width="50">Сумма</th>
    </tr>
    {{#_detail}}
    <tr>
        <td>{{no}}</td>
        <td>{{itemname}}</td>
        <td>{{measure}}</td>
        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{pricends}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td colspan="6" style="border-top: 1px solid black;" align="right">Всего:</td>
        <td width="50" style="border-top: 1px solid black;" align="right">{{total}} </td>
    </tr>
    {{#totalnds}}
    <tr style="font-weight: bolder;">
        <td colspan="6" align="right">В т.ч. НДС:</td>
        <td align="right">{{totalnds}} </td>
    </tr>
    {{/totalnds}}


</table>




