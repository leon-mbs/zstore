<table class="ctable" border="0"   cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="8">
            Рух по складу  {{store}}
        </td>
    </tr>
    <tr>

        <td align="center" colspan="8">
            Період з {{datefrom}} по {{dateto}}
        </td>
    </tr>
 

    <tr style="font-weight: bolder;">

        <th style="border: solid black 1px">Дата</th>

        <th style="border: solid black 1px">Код</th>
        <th style="border: solid black 1px">Найменування</th>

        <th align="right" style="border: solid black 1px">Поч.</th>
        <th style="border: solid black 1px">Прих.</th>
        <th align="right" style="border: solid black 1px">Витр.</th>
        <th align="right" style="border: solid black 1px">Кін.</th>
       
    </tr>
    {{#_detail}}
    <tr>

        <td>{{date}}</td>

        <td>{{code}}</td>
        <td>{{name}}</td>

        <td align="right">{{in}}</td>
        <td align="right">{{obin}}</td>
        <td align="right">{{obout}}</td>
        <td align="right">{{out}}</td>
 
    </tr>
    {{/_detail}}
     {{#showqty}} 
    <tr>

        <td></td>
     
        <td></td>
        <td align="right"><b>Всього</b></td>

        <td align="right"> </td>
        <td align="right"><b>{{bqin}}</b></td>
        <td align="right"><b>{{bqout}}</b></td>
        <td align="right"> </td>
    
    </tr>
   {{/showqty}} 
   
   {{^noshowpartion}} 
    <tr>

        <td></td>
    
        <td></td>
        <td align="right"><b>На суму</b></td>

        <td align="right"><b> </b></td>
        <td align="right"><b>{{bain}}</b></td>
        <td align="right"><b>{{baout}}</b></td>
        <td align="right"><b> </b></td>
    
    </tr>
   {{/noshowpartion}}    
</table>


