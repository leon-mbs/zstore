<table border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Фiнансовi результати
        </td>
    </tr>
    <tr>

        <td align="center" colspan="3">
            Перiод з {{datefrom}} по {{dateto}} <br>
        </td>
    </tr>
    <tr>

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

    <tr style="font-weight: bolder;">

        <td>Фiнансовi показники</td>

        <td></td>
        <td></td>

    </tr>
    <tr>
        <td>Проход:</td>
        <td align="right"> {{tu}} </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="Виручка (відпускна ціна на кількість) мінус змінні витрати (собівартість)"></i>
        </td>
    </tr>
    <tr>
        <td>Змiннi витрати :</td>
        <td align="right"> {{tvc}} </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="Cобівартість"></i>
        </td>
    </tr>
    <tr>
        <td>Операцiйнi витрати:</td>
        <td align="right">{{OP}}  </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="Витрати мінус змінні витрати (собівартість)"></i>
        </td>
    </tr>
    <tr>
        <td>Чистий прибуток:</td>
        <td align="right"> {{PR}} </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="проход мiнус  видатки"></i>
        </td>
    </tr>
    {{#isinv}}
    <tr>
        <td>Інвестиції:</td>
        <td align="right"> {{inv}} </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="ТМЦ на складі та основні фонди на балансі"></i>
        </td>
    </tr>
    <tr>
        <td>Вiддача  вiд iнвестицiй (ROI),%:</td>
        <td align="right"> {{ROI}} </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="Проход на  iнвестицiї"></i>
        </td>
    </tr>
    {{/isinv}}
</table>


