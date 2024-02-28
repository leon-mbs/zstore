<table  class="ctable"  >
 
    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Прибутки та видатки
        </td>
    </tr>
    <tr>
    <tr>

        <td align="center" colspan="3">
            Перiод з {{datefrom}} по {{dateto}} <br>
        </td>
    </tr>

        <td colspan="3">
            <b>Прибутки</b>
        </td>
    </tr>


    {{#_detail}}
    <tr>

        <td>{{type}}</td>

        <td align="right">{{in}}</td>
        <td></td>
    </tr>
    {{#docdet}}
   
    
    <tr>

        <td style="font-size:smaller">&nbsp;&nbsp;{{docdesc}}</td>

        <td style="font-size:smaller" align="right">{{indet}}</td>
       <td></td>
    </tr>   
    {{/docdet}}
    
    {{/_detail}}
    <tr style="font-weight: bolder;">

        <td align="right">Всього:</td>

        <td align="right">{{tin}}</td>
        <td></td>
    </tr>
    <tr>

        <td colspan="3">
            <b>Видатки</b> 
        </td>
    </tr>


    {{#_detail2}}
    <tr>

        <td>{{type}}</td>

        <td align="right">{{out}}</td>
        <td></td>
    </tr>
    {{#docdet}}
    
    <tr>

        <td style="font-size:smaller">&nbsp;&nbsp;{{docdesc}}</td>

        <td style="font-size:smaller" align="right">{{indet}}</td>
       <td></td>
    </tr>   
    {{/docdet}}    
    
    {{/_detail2}}
    <tr style="font-weight: bolder;">

        <td align="right">Всього:</td>

        <td align="right">{{tout}}</td>
        <td></td>
    </tr>
    <tr style="font-weight: bolder;">

        <td align="right">Баланс:</td>

        <td align="right">{{total}}</td>
        <td></td>
    </tr>

    
</table>


