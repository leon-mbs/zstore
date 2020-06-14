 
<table class="ctable"   border="0"   cellpadding="2" cellspacing="0">


    <tr>

        <td align="center" colspan="5">
            Період з {{datefrom}} по {{dateto}}    <br> <br>
        </td>
    </tr>
    {{#_type1}}
    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="5">
            Продажі по товарах  <br><br>
        </td>
    </tr> 
    <tr style="font-weight: bolder;">
        <th style="border: solid black 1px" >Найменування</th>
        <th style="border: solid black 1px" >Код</th>

        <th align="right" style="border: solid black 1px">Кіл.</th>
        <th align="right" style="border: solid black 1px">На суму</th>
        <th align="right" style="border: solid black 1px">Прибуток</th>


    </tr>
    {{#_detail}}
    <tr>


        <td>{{name}}</td>
        <td>{{code}}</td>

        <td align="right">{{qty}}</td>
        <td align="right">{{summa}} </td>
       {{#navarsign}}
        <td align="right">{{navar}} </td>
      {{/navarsign}}
      {{^navarsign}}  
        <td align="right" style="color:red">{{navar}} </td>
      {{/navarsign}}



    </tr>
    {{/_detail}}
</table>
{{/_type1}}
{{#_type2}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажі по покупцям  <br> <br>
    </td>
</tr> 
<tr style="font-weight: bolder;">
    <th colspan="3" style="border: solid black 1px" >Найменування</th>



    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
    <th align="right" style="border: solid black 1px">Прибуток</th>

    <th> </th>
</tr>
{{#_detail}}
<tr>


    <td colspan="3">{{name}}</td>



    <td align="right">{{summa}} </td>
    <td align="right">{{navar}} </td>
    <td  >  </td>
</tr>
{{/_detail}}
</table>
{{/_type2}}
{{#_type3}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажі по датах  <br> <br>
    </td>
</tr> 
<tr style="font-weight: bolder;">
    <th  style="border: solid black 1px;width:120px;" >Дата</th>

    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
    <th  > </th>
    <th  > </th>
    <th  > </th>

</tr>
{{#_detail}}
<tr>


    <td  >{{dt}}</td>



    <td align="right">{{summa}} </td>
    <td  >  </td>
    <td  >  </td>
    <td  >  </td>

</tr>
{{/_detail}}
</table>
{{/_type3}}
{{#_type4}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Послуги та роботи  <br><br>
    </td>
</tr> 
<tr style="font-weight: bolder;">
    <th style="border: solid black 1px" >Найменування</th>


    <th align="right" style="border: solid black 1px;width:60px;">Кіл.</th>
    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
    <th  > </th>
    <th  > </th>
</tr>
{{#_detail}}
<tr>


    <td>{{name}}</td>


    <td align="right">{{qty}}</td>
    <td align="right">{{summa}} </td>
    <td  >  </td>
    <td  >  </td>
</tr>
{{/_detail}}
</table>
{{/_type4}}

{{#_type5}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажi  по категорiях <br> <br>
    </td>
</tr> 
<tr style="font-weight: bolder;">
    <th colspan="3" style="border: solid black 1px" >Найменування</th>



    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
    <th align="right" style="border: solid black 1px">Прибуток</th>

    <th  > </th>
</tr>
{{#_detail}}
<tr>


    <td colspan="3">{{name}}</td>



    <td align="right">{{summa}} </td>
    <td align="right">{{navar}} </td>
    <td  >  </td>
</tr>
{{/_detail}}
</table>
{{/_type5}}
