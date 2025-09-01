<table class="ctable" border="0"   cellpadding="2" cellspacing="0">


    <tr>

        <td align="center" colspan="4">
            Період з {{datefrom}} по {{dateto}} <br> <br>
        </td>
    </tr>
    {{#_type1}}
    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="4">
            Закупівлі по товарам <br><br>
        </td>
    </tr>
    <tr style="font-weight: bolder;">
        <th style="border: solid black 1px">Найменування</th>
        <th style="border: solid black 1px">Код</th>

        <th align="right" style="border: solid black 1px">Кіл.</th>
        <th align="right" style="border: solid black 1px">На суму</th>

    </tr>
    {{#_detail}}
    <tr>


        <td>{{name}}</td>
        <td>{{code}}</td>

        <td align="right">{{qty}}</td>
        <td align="right">{{summa}}</td>

    </tr>
    {{/_detail}}
    <tr>  <td> </td>
        <td> </td>

        <td  > </td>
        <td align="right"><b>{{total}}</b></td>    </tr>   
</table>
{{/_type1}}
{{#_type2}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="4">
        Закупівлі по постачальникам <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="2" style="border: solid black 1px">Найменування</th>


    <th align="right" style="border: solid black 1px; ">На суму</th>
    <th></th>
</tr>
{{#_detail}}
<tr>


    <td colspan="2">{{name}}</td>


    <td align="right">{{summa}}</td>
    <td></td>
</tr>
{{/_detail}}
<tr>  <td colspan="2"> </td>
    <td align="right"><b>{{total}}</b></td>    
    <td> </td>     </tr>
</table>
{{/_type2}}
{{#_type3}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="4">
        Закупівлі по датам <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th style="border: solid black 1px;width:120px;">Дата</th>

    <th align="right" style="border: solid black 1px; ">На суму</th>
    <th></th>
    <th></th>

</tr>
{{#_detail}}
<tr>


    <td>{{dt}}</td>


    <td align="right">{{summa}}</td>
    <td></td>
    <td></td>

</tr>
{{/_detail}}
<tr>  <td  > </td>
    <td align="right"><b>{{total}}</b></td>    
    <td> </td>    <td> </td>     </tr>
</table>
{{/_type3}}
{{#_type4}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
         Послуги та роботи <br><br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="2" style="border: solid black 1px">Найменування</th>

    
    <th align="right" style="border: solid black 1px; ">Кіл.</th>
    <th align="right" style="border: solid black 1px; ">На суму</th>
    
 
</tr>
{{#_detail}}
<tr>


    <td colspan="2">{{name}}</td>


    <td align="right">{{qty}}</td>
    <td align="right">{{summa}}</td>
 
</tr>
{{/_detail}}
<tr><td colspan="3" ></td> <td align="right" ><b>{{totsumma}}</b></td>  </tr>

</table>

{{/_type4}}
{{#_type5}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Закупівлі по категорiям <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="3" style="border: solid black 1px">Найменування</th>


    <th align="right" style="border: solid black 1px; ">На суму</th>
 

 
</tr>
{{#_detail}}
<tr>


    <td colspan="3">{{name}}</td>


    <td align="right">{{summa}}</td>
 
  
 
</tr>
{{/_detail}}
<tr><td colspan="3" ></td> <td align="right" ><b>{{totsumma}}</b></td> 
 


</tr>

</table>
{{/_type5}}   
