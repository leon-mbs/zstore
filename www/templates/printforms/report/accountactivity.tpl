<table class="ctable" border="0"   cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="7">
            Рух по рахунку  {{acc}}
        </td>
    </tr>
    <tr>

        <td align="center" colspan="7">
            Період з {{datefrom}} по {{dateto}}
        </td>
    </tr>
 

    <tr  >
 
        <th rowspan="2" style="border: solid black 1px">Дата</th>

      
        <th colspan="2" style="border: solid black 1px">Поч. сальдо</th>
        <th colspan="2" style="border: solid black 1px">Оборот</th>
        <th colspan="2" style="border: solid black 1px">Кін. сальдо.</th>
        
    </tr>
    <tr  >
 
        <th   style="border: solid black 1px">Дебет</th>
        <th   style="border: solid black 1px">Кредит</th>
        <th   style="border: solid black 1px">Дебет</th>
        <th   style="border: solid black 1px">Кредит</th>
        <th   style="border: solid black 1px">Дебет</th>
        <th   style="border: solid black 1px">Кредит</th>

      
    
    </tr>
    {{#_detail}}
    <tr>

        <td>{{date}}</td>

        <td align="right">{{startdt}}</td>
        <td align="right">{{startct}}</td>

        <td align="right">{{amountdt}}</td>
        <td align="right">{{amountct}}</td>
        <td align="right">{{enddt}}</td>
        <td align="right">{{endct}}</td>
       
 
    </tr>
    {{/_detail}}
 
    
  
   
  
</table>


