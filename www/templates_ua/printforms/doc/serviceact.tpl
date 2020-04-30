<table class="ctable" border="0" cellpadding="2" cellspacing="0">
    <tr>
        <td colspan="4">
            Замовник: {{customer_name}}
        </td>


    </tr>

    {{#isdevice}}
    <tr>
        <td colspan="4">
            Виріб, матеріали: {{device}} с/н: {{devsn}}
        </td>

    </tr>
    {{/isdevice}}
    <tr style="font-weight: bolder;">
        <td colspan="4" align="center">
            Акт виконаних робіт № {{document_number}} від {{date}}       </td>
    </tr>

</table>
<br>
<table class="ctable" width="600" cellspacing="0" cellpadding="1" border="0">
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;">Найменування</th>
        <th style="border: 1px solid black;">Опис</th>
        <th style="border: 1px solid black;" width="50" align="right">Вартість</th>

    </tr>
    {{#_detail}}
    <tr>
        <td>{{no}}</td>
        <td>{{service_name}}</td>

        <td>{{desc}}</td>

        <td align="right">{{price}}</td>

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td colspan="3" style="border-top: 1px solid black;" align="right">Всього:</td>
        <td style="border-top: 1px solid black;" align="right">{{total}} </td>
    </tr>
    <tr style="font-weight: bolder;">
        <td colspan="3" align="right">До оплати:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    <tr style="font-weight: bolder;">
        <td colspan="3" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
    <tr>
        <td colspan="4"><br>
            Гарантія: {{gar}}
        </td>
    </tr>

</table>


