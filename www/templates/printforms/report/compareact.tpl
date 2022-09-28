<table class="ctable" border="0" class="ctable" cellpadding="2" cellspacing="0">


    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="4">
            Акт звiрки  вiд {{date}}<br>
        </td>
    </tr>
   <tr style=" font-weight: bolder;">
        <td  colspan="4">
            Контрагент {{cust}}<br><br>
        </td>
    </tr>
    <tr style="font-weight: bolder;">
        <th style="border: solid black 1px">Дата</th>
        <th style="border: solid black 1px">Документ</th>

        <th align="right" style="border: solid black 1px">Дебет</th>
        <th align="right" style="border: solid black 1px">Кредит</th>
        <th align="right" style="border: solid black 1px">Сальдо</th>

    </tr>
    {{#_detail}}
    <tr>

        <td>{{document_date}}</td>

        <td>{{meta_desc}} {{document_number}}</td>

        <td align="right">{{active}}</td>
        <td align="right">{{passive}}</td>
        <td align="right">{{bal}}</td>

    </tr>
    {{/_detail}}
