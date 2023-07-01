<table class="ctable" border="0" class="ctable" cellpadding="2" cellspacing="0">


    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="4">
            Зарезервованi товари на {{date}}<br>
        </td>
    </tr>
   
    <tr style="font-weight: bolder;">
        <th style="border: solid black 1px">Товар</th>
        <th style="border: solid black 1px">Код</th>
        <th style="border: solid black 1px">Склад</th>
        <th style="border: solid black 1px">Документ</th>
        <th style="border: solid black 1px">Покупець</th>

        <th align="right" style="border: solid black 1px">Кіл.</th>

    </tr>
    {{#_detail}}
    <tr>

        <td>{{itemname}}</td>
        <td>{{item_code}}</td>

        <td>{{store}}</td>
        <td>{{document_number}}</td>
        <td>{{customer}}</td>

        <td align="right">{{qty}}</td>

    </tr>
    {{/_detail}}
