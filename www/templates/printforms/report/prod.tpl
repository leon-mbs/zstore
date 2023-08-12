<table class="ctable" border="0"  >

    <tr>
        <td align="center" colspan="4">
            <h4> Звіт по виробництву </h4>
        </td>
    </tr>
    <tr>

        <td colspan="4">
            Період з <b>{{datefrom}}</b> по <b>{{dateto}}</b> <br>
        </td>
    </tr>
    {{#parea}}
    <tr>

        <td colspan="4">
            Виробнича ділянка <b>{{parea}}</b><br>
        </td>
    </tr>
    {{/parea}}
    <tr>

        <td colspan="4">
            <h5> Списано на виробництво </h5>
        </td>
    </tr>

    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px">Найменування</th>

        <th style="border: solid black 1px">Код</th>
        <th align="right" style="border: solid black 1px">Кіл.</th>


        <th align="right" style="border: solid black 1px">Сума</th>

        {{#_detail}}
    <tr>


        <td>{{name}}</td>

        <td align="right">{{code}}</td>
        <td align="right">{{qty}}</td>


        <td align="right">{{summa}}</td>

    </tr>
    {{/_detail}}
    <tr>

        <td align="right" colspan="3">
            <b> На суму </b>
        </td>
        <td align="right"><b>{{sum1}}</b></td>
    </tr>

    <tr>

        <td colspan="4"><br>
            <h5> Оприбутковано з виробництва </h5>
        </td>
    </tr>

    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px">Найменування</th>

        <th style="border: solid black 1px">Код</th>
        <th align="right" style="border: solid black 1px">Кіл.</th>


        <th align="right" style="border: solid black 1px">Сума</th>

        {{#_detail2}}
    <tr>


        <td>{{name}}</td>

        <td align="right">{{code}}</td>
        <td align="right">{{qty}}</td>


        <td align="right">{{summa}}</td>

    </tr>
    {{/_detail2}}
    <tr>

        <td align="right" colspan="3">
            <b> На суму </b>
        </td>
        <td align="right"><b>{{sum2}}</b></td>
    </tr>
    
    <tr>

        <td colspan="4"><br>
            <h5> Готово до виробництва </h5>
            <small>Напівфабрикати та готова продукція, які можуть бути виготовлені на <b> {{currdate}} </b> <br>відповідно до комплектів. 
             Якщо матеріал або комплектуючі входять <br>в кілька позицій, будуть показані всі варіанти.
            
        </td>
    </tr>

    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px">Найменування</th>

        <th style="border: solid black 1px">Код</th>
        <th colspan="2" align="right" style="border: solid black 1px">Кіл.</th>




        {{#_detail3}}
    <tr>


        <td>{{name}}</td>

        <td align="right">{{code}}</td>
        <td colspan="2" align="right">{{qty}}</td>




    </tr>
    {{/_detail3}}

</table>


