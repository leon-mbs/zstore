<table class="ctable" border="0" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="9">
            Движение по складу
        </td>
    </tr>
    <tr>

        <td align="center" colspan="9">
            Период с {{datefrom}} по {{dateto}}&nbsp;&nbsp;&nbsp;&nbsp; 
        </td>
    </tr>

    <tr style="font-weight: bolder;">

        <th style="border: solid black 1px">Дата</th>
        <th style="border: solid black 1px">Склад</th>
        <th style="border: solid black 1px">Код</th>
        <th style="border: solid black 1px">Наименование</th>

        <th align="right" style="border: solid black 1px">Нач.</th>
        <th style="border: solid black 1px">Прих.</th>
        <th align="right" style="border: solid black 1px">Расх.</th>
        <th align="right" style="border: solid black 1px">Кон.</th>
        <th style="border: solid black 1px">Документы</th>
    </tr>
    {{#_detail}}       
    <tr>

        <td>{{date}}</td>
        <td>{{store}}</td>
        <td>{{code}}</td>
        <td>{{name}}</td>

        <td align="right">{{in}}</td>
        <td align="right">{{obin}}</td>
        <td align="right">{{obout}}</td>
        <td align="right">{{out}}</td>
        <td> {{{documents}}}</td>
    </tr>
    {{/_detail}}
    {{^noshowpartion}}
    <tr>

        <td></td>
        <td></td>
        <td></td>
        <td align="right"><b>На сумму</b></td>

        <td align="right"><b>{{ba}}</b></td>
        <td align="right"><b>{{bain}}</b></td>
        <td align="right"><b>{{baout}}</b></td>
        <td align="right"><b>{{baend}}</b></td>
        <td> </td>
    </tr>
  {{/noshowpartion}}    
</table>


