 
<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <th width="30">&nbsp;</th>
        <th width="100">&nbsp;</th>
        <th width="150">&nbsp;</th>

        <th width="60">&nbsp;</th>
        <th width="60">&nbsp;</th>
        <th width="80">&nbsp;</th>
    </tr>



    <tr>
        <td></td>
        <td>Заказчик</td>
        <td colspan="6">{{customername}}</td>
    </tr>
    <tr>
        <td></td>
        <td>Телефон</td>
        <td colspan="6">{{phone}}</td>
    </tr>
    <tr>
        <td></td>
        <td>Email</td>
        <td colspan="6">{{email}}</td>
    </tr>

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="7" valign="middle">
            Заказ № {{document_number}} от {{date}}   
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th colspan="2"   style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Наименование</th>
        <th colspan="2"   style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>

        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Кол.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Цена</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сумма</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td colspan="2">{{tovar_name}}</td>
        <td colspan="2">{{tovar_code}}</td>

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="7" align="right">Итого:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>

    <tr>
        <td  >  </td>
        <td valign="top" > Доставка</td>
        <td colspan="4">
            <p>{{delivery}}</p>
        </td>
    </tr>
    <tr>
        <td  >  </td>
        <td valign="top" > ТТН</td>
        <td colspan="4">
            <p>{{ttn}}</p>
        </td>
    </tr>

    <tr>
        <td  >  </td>
        <td valign="top" > Коментарий</td>
        <td colspan="4">
            <p>{{notes}}</p>
        </td>
    </tr>
</table>

