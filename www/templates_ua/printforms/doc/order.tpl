<table class="ctable" border="0" cellspacing="0" cellpadding="2">

    <tr>
        <td></td>
        <td>Замовник</td>
        <td colspan="7">{{customer_name}}</td>
    </tr>
    <tr>
        <td></td>
        <td>Телефон</td>
        <td colspan="7">{{phone}}</td>
    </tr>
    <tr>
        <td></td>
        <td>Email</td>
        <td colspan="7">{{email}}</td>
    </tr>

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="9" valign="middle">
            Замовлення № {{document_number}} від {{date}}
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Найменування
        </th>
        <th colspan="2" style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Од.</th>

        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Кіл.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Ціна</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" align="right">Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right" valign="top">{{no}}</td>
        <td colspan="2">{{{tovar_name}}} </td>
        <td colspan="2" valign="top">{{tovar_code}}</td>
        <td valign="top">{{msr}}</td>

        <td align="right" valign="top">{{quantity}}</td>
        <td align="right" valign="top">{{price}}</td>
        <td align="right" valign="top">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="8" align="right">Разом:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>

    <tr>
        <td></td>
        <td valign="top"> Доставка</td>
        <td colspan="4">
            <p>{{delivery}}</p>
        </td>
    </tr>
    <tr>
        <td></td>
        <td valign="top"> ТТН</td>
        <td colspan="4">
            <p>{{ttn}}</p>
        </td>
    </tr>

    <tr>
        <td></td>
        <td valign="top"> Коментар</td>
        <td colspan="4">
            <p>{{notes}}</p>
        </td>
    </tr>
</table>

