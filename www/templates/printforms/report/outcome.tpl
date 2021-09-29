<table class="ctable" border="0" cellpadding="2" cellspacing="0">


    <tr>

        <td align="center" colspan="5">
            Период с {{datefrom}} по {{dateto}} <br> <br>
        </td>
    </tr>
    {{#_type1}}
    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="5">
            Продажи по товарам <br><br>
        </td>
    </tr>
    <tr style="font-weight: bolder;">
        <th style="border: solid black 1px">Наименование</th>
        <th style="border: solid black 1px">Код</th>

        <th align="right" style="border: solid black 1px">Кол.</th>
        <th align="right" style="border: solid black 1px">На сумму</th>
        <th align="right" style="border: solid black 1px">Прибыль</th>


    </tr>
    {{#_detail}}
    <tr>


        <td>{{name}}</td>
        <td>{{code}}</td>

        <td align="right">{{qty}}</td>
        <td align="right">{{summa}}</td>
        {{#navarsign}}
        <td align="right">{{navar}}</td>
        {{/navarsign}}
        {{^navarsign}}
        <td align="right" style="color:red">{{navar}}</td>
        {{/navarsign}}

    </tr>
    {{/_detail}}
    <tr><td colspan="3" align="right"><b>Всего:</b></td><td align="right" ><b>{{totsumma}}</b></td><td align="right" ><b><b>{{totnavar}}</b></b></td> </tr>

</table>
{{/_type1}}
{{#_type2}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажи по покупателям <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="3" style="border: solid black 1px">Наименование</th>


    <th align="right" style="border: solid black 1px;width:100px;">На сумму</th>
    <th align="right" style="border: solid black 1px">Прибыль</th>

    <th></th>
</tr>
{{#_detail}}
<tr>


    <td colspan="3">{{name}}</td>


    <td align="right">{{summa}}</td>
    <td align="right">{{navar}}</td>
    <td></td>
</tr>
{{/_detail}}
<tr><td colspan="3"></td><td align="right" ><b>{{totsumma}}</b></td><td align="right" ><b><b>{{totnavar}}</b></b></td> </tr>

</table>
{{/_type2}}
{{#_type3}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="6">
        Продажи по датам <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th style="border: solid black 1px;width:120px;">Дата</th>

    <th align="right" style="border: solid black 1px;width:100px;">На сумму</th>
    <th align="right" style="border: solid black 1px;width:100px;">Прибыль</th>
    <th></th>
    <th></th>
    <th></th>

</tr>
{{#_detail}}
<tr>


    <td>{{dt}}</td>


    <td align="right">{{summa}}</td>
    <td align="right">{{navar}}</td>
    <td></td>
    <td></td>
    <td></td>

</tr>
{{/_detail}}
<tr><td  ></td><td align="right" ><b>{{totsumma}}</b></td> <td align="right"  ><b>{{totnavar}}</b> </td><td   > </td><td   > </td><td   > </td> </tr>

</table>
{{/_type3}}
{{#_type4}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Услуги и работы <br><br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th style="border: solid black 1px">Наименование</th>


    <th align="right" style="border: solid black 1px;width:60px;">Кол.</th>
    <th align="right" style="border: solid black 1px;width:100px;">На сумму</th>
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
        Продажи по категориям <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="3" style="border: solid black 1px">Наименование</th>


    <th align="right" style="border: solid black 1px;width:100px;">На сумму</th>
    <th align="right" style="border: solid black 1px">Прибыль</th>

    <th></th>
</tr>
{{#_detail}}
<tr>


    <td colspan="3">{{name}}</td>


    <td align="right">{{summa}}</td>
    <td align="right">{{navar}}</td>
    <td></td>
</tr>
{{/_detail}}
<tr><td colspan="3" ></td> <td align="right" ><b>{{totsumma}}</b></td> <td align="right"   > <b>{{totnavar}}</b></td><td   > </td>  </tr>

</table>
{{/_type5}}

{{#_type6}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажи по компаниям <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="3" style="border: solid black 1px">Наименование</th>


    <th align="right" style="border: solid black 1px;width:100px;">На сумму</th>
    <th align="right" style="border: solid black 1px">Прибыль</th>

    <th></th>
</tr>
{{#_detail}}
<tr>


    <td colspan="3">{{name}}</td>


    <td align="right">{{summa}}</td>
    <td align="right">{{navar}}</td>
    <td></td>
</tr>
{{/_detail}}
<tr><td colspan="3" ></td> <td align="right" ><b>{{totsumma}}</b></td> <td align="right"   > <b>{{totnavar}}</b></td><td   > </td>  </tr>

</table>
{{/_type6}}

{{#_type7}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажи по складам <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="3" style="border: solid black 1px">Наименование</th>


    <th align="right" style="border: solid black 1px;width:100px;">На сумму</th>
    <th align="right" style="border: solid black 1px">Прибыль</th>

    <th></th>
</tr>
{{#_detail}}
<tr>


    <td colspan="3">{{name}}</td>


    <td align="right">{{summa}}</td>
    <td align="right">{{navar}}</td>
    <td></td>
</tr>
{{/_detail}}
<tr><td colspan="3" ></td> <td align="right" ><b>{{totsumma}}</b></td> <td align="right"   > <b>{{totnavar}}</b></td><td   > </td>  </tr>

</table>
{{/_type7}}
{{#_type8}}
<tr style="font-size:larger; font-weight: bolder;">
    <td align="center" colspan="5">
        Продажи по источникам <br> <br>
    </td>
</tr>
<tr style="font-weight: bolder;">
    <th colspan="2" style="border: solid black 1px">Наименование</th>

          <th align="right" style="border: solid black 1px">Кол.</th>

    <th align="right" style="border: solid black 1px;width:100px;">На сумму</th>
    <th align="right" style="border: solid black 1px">Прибыль</th>

  
</tr>
{{#_detail}}
<tr>


    <td colspan="2">{{name}}</td>


    <td align="right">{{qty}}</td>
    <td align="right">{{summa}}</td>
    <td align="right">{{navar}}</td>
    
</tr>
{{/_detail}}
<tr><td colspan="3" ></td> <td align="right" ><b>{{totsumma}}</b></td> <td align="right"   > <b>{{totnavar}}</b></td><td   > </td>  </tr>

</table>
{{/_type8}}
