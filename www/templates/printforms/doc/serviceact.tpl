<table class="ctable" border="0" cellpadding="2" cellspacing="0">
    <tr>
        <td colspan="4">
            Заказчик: {{customer_name}}
        </td>


    </tr>

    {{#isdevice}}
    <tr>
        <td colspan="4">
            Изделие, материалы: {{device}} с/н: {{devsn}}
        </td>

    </tr>
    {{/isdevice}}
    <tr style="font-weight: bolder;">
        <td colspan="4" align="center">
            Акт выполненых работ № {{document_number}} от {{date}}       </td>
    </tr>

</table>
<br>
<table class="ctable" width="600" cellspacing="0" cellpadding="1" border="0">
    <tr style="font-weight: bolder;">
        <th width="20" style="border: 1px solid black;">№</th>
        <th style="border: 1px solid black;">Наименование</th>
        <th style="border: 1px solid black;">Описание</th>
        <th style="border: 1px solid black;" width="50" align="right">Стоимость</th>

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
        <td colspan="3" style="border-top: 1px solid black;" align="right">Всего:</td>
        <td style="border-top: 1px solid black;" align="right">{{total}} </td>
    </tr>
    <tr style="font-weight: bolder;">
        <td colspan="3" align="right">К оплате:</td>
        <td align="right">{{payamount}}</td>
    </tr>
    <tr style="font-weight: bolder;">
        <td colspan="3" align="right">Оплата:</td>
        <td align="right">{{payed}}</td>
    </tr>
    <tr>
        <td colspan="4"><br>
            Гарантия: {{gar}}
        </td>
    </tr>

</table>


