<table class="ctable" border="0"    >

    <tr>
        <td align="center" colspan="3">
            <h4> Звіт по кафе </h4>
        </td>
    </tr>
    <tr>

        <td colspan="3">
            Період з <b>{{datefrom}}</b> по <b>{{dateto}}</b> <br>
        </td>
    </tr>
    {{#isd1}}
 
    <tr>

        <td colspan="3">
            <h5> Замовлення </h5>
        </td>
    </tr>

    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px">Дата</th>

        <th style="border: solid black 1px">День</th>
        <th align="right" style="border: solid black 1px">Кіл.</th>


   

        {{#_detail}}
    <tr>


        <td>{{dt}}</td>

        <td  >{{day}}</td>
        <td align="right">{{qty}}</td>

       
       

    </tr>
    {{/_detail}}

    {{/isd1}}
 
   {{#isd2}}
    <tr>

        <td colspan="3"><br>
            <h5>Позиції   </h5>
        </td>
    </tr>

    <tr style="font-weight: bolder;">


        <th style="border: solid black 1px">Найменування</th>

        <th align="right" style="border: solid black 1px">Кіл.</th>
        <th align="right" style="border: solid black 1px">На суму</th>

        
        {{#_detail2}}
    <tr>


        <td>{{name}}</td>

        <td align="right" >{{qty}}</td>
        <td align="right">{{sum}}</td>

        
    </tr>
    {{/_detail2}}
 
   {{/isd2}}   
 

</table>


