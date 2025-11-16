<table class="ctable" border="0"   cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="7">
           Оборотно-сальдова вiдомiсть
        </td>
    </tr>
    <tr>

        <td align="center" colspan="7">
            Період з {{datefrom}} по {{dateto}}
        </td>
    </tr>
 

    <tr  >
 
        <th rowspan="2" style="border: solid black 1px">Рахунок</th>

      
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

        <td>{{acc}}</td>

        <td align="right">{{startdt}}</td>
        <td align="right">{{startct}}</td>

        <td align="right">{{amountdt}}</td>
        <td align="right">{{amountct}}</td>
        <td align="right">{{enddt}}</td>
        <td align="right">{{endct}}</td>
       
 
    </tr>
    {{/_detail}}
 
    <tr style="font-weight:bolder">

        <td> </td>

        <td align="right">{{bdt}}</td>
        <td align="right">{{bct}}</td>

        <td align="right"></td>
        <td align="right"></td>
        <td align="right">{{edt}}</td>
        <td align="right">{{ect}}</td>
       
 
    </tr>

 
  
   
  
</table>


