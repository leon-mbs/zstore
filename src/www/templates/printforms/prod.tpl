 
<table class="ctable"   border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="4">
            Отчет по производству
        </td>
    </tr>            
    <tr>

        <td align="center" colspan="4">
            Период с {{datefrom}} по {{dateto}}  <br>
        </td>
    </tr>
    <tr>

        <td   colspan="4">
            Списано на производство 
        </td>
    </tr>

    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px" >Наименование</th>

        <th   style="border: solid black 1px">Код</th>
        <th align="right" style="border: solid black 1px">Кол.</th>


        <th align="right" style="border: solid black 1px">Сумма</th>

        {{#_detail}}
    <tr>


        <td>{{name}}</td>

        <td align="right">{{code}}</td>
        <td align="right">{{qty}}</td>


        <td align="right">{{summa}}</td>

    </tr>
    {{/_detail}}
    <tr>

        <td  align="right" colspan="3">  
            <b>   На сумму </b>
        </td>
        <td align="right"><b>{{sum1}}</b></td>
    </tr>      

    <tr>

        <td   colspan="4"> <br>
            Оприходовано  с  производства 
        </td>
    </tr>

    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px" >Наименование</th>

        <th   style="border: solid black 1px">Код</th>
        <th align="right" style="border: solid black 1px">Кол.</th>


        <th align="right" style="border: solid black 1px">Сумма</th>

        {{#_detail2}}
    <tr>


        <td>{{name}}</td>

        <td align="right">{{code}}</td>
        <td align="right">{{qty}}</td>


        <td align="right">{{summa}}</td>

    </tr>
    {{/_detail2}}  
    <tr>

        <td  align="right" colspan="3">  
            <b> На сумму   </b>
        </td>
        <td align="right"><b>{{sum2}}</b></td>
    </tr>                   
</table>


