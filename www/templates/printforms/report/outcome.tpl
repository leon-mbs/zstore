<table class="ctable" border="0" cellpadding="2" cellspacing="0">


    <tr>

        <td align="center" colspan="5">
            Період з {{datefrom}} по {{dateto}} <br> <br>
        </td>
    </tr>
    {{#_type1}}
    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="5">
            Продажі по товарах <br><br>
        </td>
    </tr>
    <tr style="font-weight: bolder;">
        <th style="border: solid black 1px">Найменування</th>
        <th style="border: solid black 1px">Код</th>

        <th align="right" style="border: solid black 1px">Кіл.</th>
        <th align="right" style="border: solid black 1px">На суму</th>
      {{^noshowpartion}}
        <th align="right" style="border: solid black 1px">Прибуток</th>
      {{/noshowpartion}}


    </tr>
    {{#_detail}}
    <tr>


        <td>{{name}}</td>
        <td>{{code}}</td>

        <td align="right">{{qty}}</td>
        <td align="right">{{summa}}</td>

      {{^noshowpartion}}


        {{#navarsign}}
        <td align="right">{{navar}}</td>
        {{/navarsign}}
        {{^navarsign}}
        <td align="right" style="color:red">{{navar}}</td>
        {{/navarsign}}

      {{/noshowpartion}}


    </tr>
    {{/_detail}}
    <tr><td colspan="3"></td><td align="right" ><b>{{totsumma}}</b></td>
      {{^noshowpartion}}

    <td align="right" ><b><b>{{totnavar}}</b></b></td> 
      {{/noshowpartion}}
    
    
    </tr>

</table>
{{/_type1}}
{{#_type2}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажі по покупцям <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="3" style="border: solid black 1px">Найменування</th>


    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
    
        {{^noshowpartion}}

    <th align="right" style="border: solid black 1px">Прибуток</th>
    {{/noshowpartion}}

    <th></th>
</tr>
{{#_detail}}
<tr>


    <td colspan="3">{{name}}</td>


    <td align="right">{{summa}}</td>
    {{^noshowpartion}}
    
    <td align="right">{{navar}}</td>
    {{/noshowpartion}}
    
    <td></td>
</tr>
{{/_detail}}
<tr><td colspan="3"></td><td align="right" ><b>{{totsumma}}</b></td>
    {{^noshowpartion}}

<td align="right" ><b><b>{{totnavar}}</b></b></td> 
    {{/noshowpartion}}

</tr>

</table>
{{/_type2}}
{{#_type3}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажі по датах <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th style="border: solid black 1px;width:120px;">Дата</th>

    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
    <th></th>
    <th></th>
    <th></th>

</tr>
{{#_detail}}
<tr>


    <td>{{dt}}</td>


    <td align="right">{{summa}}</td>
    <td></td>
    <td></td>
    <td></td>

</tr>
{{/_detail}}
<tr><td  ></td><td align="right" ><b>{{totsumma}}</b></td> <td   > </td><td   > </td><td   > </td> </tr>

</table>
{{/_type3}}
{{#_type4}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Послуги та роботи <br><br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th style="border: solid black 1px">Найменування</th>


    <th align="right" style="border: solid black 1px;width:60px;">Кіл.</th>
    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
    <th></th>
    <th></th>
</tr>
{{#_detail}}
<tr>


    <td>{{name}}</td>


    <td align="right">{{qty}}</td>
    <td align="right">{{summa}}</td>
    <td></td>
    <td></td>
</tr>
{{/_detail}}
<tr><td  ></td><td  ></td><td align="right" ><b>{{totsumma}}</b></td> <td   > </td><td   > </td>  </tr>

</table>
{{/_type4}}

{{#_type5}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажi по категорiях <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="3" style="border: solid black 1px">Найменування</th>


    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
 
     {{^noshowpartion}}

    <th align="right" style="border: solid black 1px">Прибуток</th>
    {{/noshowpartion}}

    <th></th>
</tr>
{{#_detail}}
<tr>


    <td colspan="3">{{name}}</td>


    <td align="right">{{summa}}</td>
      {{^noshowpartion}}

  <td align="right">{{navar}}</td>
    {{/noshowpartion}}
  
    <td></td>
</tr>
{{/_detail}}
<tr><td colspan="3" ></td> <td align="right" ><b>{{totsumma}}</b></td> 
    {{^noshowpartion}}

<td align="right"   > <b>{{totnavar}}</b></td>
    {{/noshowpartion}}

<td   > </td>  </tr>

</table>
{{/_type5}}     

{{#_type6}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажi по компанiях <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="3" style="border: solid black 1px">Найменування</th>


    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
      {{^noshowpartion}}

    <th align="right" style="border: solid black 1px">Прибуток</th>
    {{/noshowpartion}}

    <th></th>
</tr>
{{#_detail}}
<tr>


    <td colspan="3">{{name}}</td>


    <td align="right">{{summa}}</td>
     {{^noshowpartion}}

   <td align="right">{{navar}}</td>
    {{/noshowpartion}}
   
    <td></td>
</tr>
{{/_detail}}
<tr><td colspan="3" ></td> <td align="right" ><b>{{totsumma}}</b></td>
    {{^noshowpartion}}

 <td align="right"   > <b>{{totnavar}}</b></td>
    {{/noshowpartion}}
 
 <td   > </td>  </tr>

</table>
{{/_type6}}     

{{#_type7}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажi по складах <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="3" style="border: solid black 1px">Найменування</th>


    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
     {{^noshowpartion}}

   <th align="right" style="border: solid black 1px">Прибуток</th>
    {{/noshowpartion}}

    <th></th>
</tr>
{{#_detail}}
<tr>


    <td colspan="3">{{name}}</td>


    <td align="right">{{summa}}</td>
      {{^noshowpartion}}

  <td align="right">{{navar}}</td>
    {{/noshowpartion}}
  
    <td></td>
</tr>
{{/_detail}}
<tr><td colspan="3" ></td> <td align="right" ><b>{{totsumma}}</b></td
     {{^noshowpartion}}

 <td align="right"   > <b>{{totnavar}}</b></td>
    {{/noshowpartion}}
 
 <td   > </td>  </tr>

</table>
{{/_type7}}     
{{#_type8}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажi по джерелах <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="2" style="border: solid black 1px">Найменування</th>

         <th align="right" style="border: solid black 1px">Кіл.</th>

    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
      {{^noshowpartion}}

    <th align="right" style="border: solid black 1px">Прибуток</th>
    {{/noshowpartion}}

    
</tr>
{{#_detail}}
<tr>


    <td colspan="2">{{name}}</td>


    <td align="right">{{qty}}</td>
    <td align="right">{{summa}}</td>
      {{^noshowpartion}}

  <td align="right">{{navar}}</td>
    {{/noshowpartion}}
     
</tr>
{{/_detail}}
<tr><td colspan="3" ></td> <td align="right" ><b>{{totsumma}}</b></td> 
    {{^noshowpartion}}

    <td align="right"   > <b>{{totnavar}}</b></td>
    {{/noshowpartion}}

    <td   > </td>  </tr>

</table>
{{/_type8}}     
{{#_type12}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Товари по бренду  {{brand}}<br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="2" style="border: solid black 1px">Найменування</th>

          <th align="right" style="border: solid black 1px">Кіл.</th>

    <th align="right" style="border: solid black 1px;width:100px;">На суму</th>
      {{^noshowpartion}}

  <th align="right" style="border: solid black 1px">Прибуток</th>
    {{/noshowpartion}}

  
</tr>
{{#_detail}}
<tr>


    <td colspan="2">{{name}}</td>


    <td align="right">{{qty}}</td>
    <td align="right">{{summa}}</td>
    {{^noshowpartion}}

    <td align="right">{{navar}}</td>
    {{/noshowpartion}}
    
</tr>
{{/_detail}}
<tr><td colspan="3" ></td> <td align="right" ><b>{{totsumma}}</b></td> 
    {{^noshowpartion}}
<td align="right"   > <b>{{totnavar}}</b></td>
    {{/noshowpartion}}

<td   > </td>  </tr>

</table>
{{/_type12}}
