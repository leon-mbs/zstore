<html>
<head>
<title></title>
</head>
<body>

Information:<p>

Name: {$name|capitalize}<br>
Addr: {$address|escape}<br>
Date: {$smarty.now|date_format:"%Y-%m-%d"}<br>

</body>
</html><table class="ctable" border="0" cellpadding="1" cellspacing="0" {{{style}}}>
    <tr>
        <td colspan="3">ТТН №{{document_number}}</td>
    </tr>
    <tr>
        <td colspan="3">від {{date}}</td>
    </tr>
    {{#ship_number}}
    <tr>
        <td colspan="3">Експрес-накладна: {{ship_number}}</td>
    </tr>
    {{/ship_number}}
    {{#order}}
    <tr>
        <td colspan="3">Замовлення: {{order}}</td>
    </tr>
    {{/order}}
    {{#isfirm}}
    <tr>
        <td colspan="3">Відправник:</td>
    </tr>
    <tr>
        <td colspan="3">{{firm_name}}</td>
    </tr>
    {{/isfirm}}
    {{#customer_name}}
    <tr>
        <td colspan="3">Отримувач:</td>
    </tr>
    <tr>
        <td colspan="3">{{customer_name}}</td>
    </tr>
    {{/customer_name}}

    {{#_detail}}
    <tr>
        <td colspan="3">{{tovar_name}}</td>
    </tr>
    <tr>
        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr>
        <td colspan="2" align="right">Всього:</td>
        <td align="right">{{total}}</td>
    </tr>
    {{#sent_date}}
    <tr>
        <td colspan="3">Відправлено: {{sent_date}}</td>
    </tr>
    {{/sent_date}}
</table>