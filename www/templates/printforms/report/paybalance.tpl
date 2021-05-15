<table border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Финансовые результаты
        </td>
    </tr>
    <tr>

        <td align="center" colspan="3">
            Период с {{datefrom}} по {{dateto}} <br>
        </td>
    </tr>
    <tr>

        <td colspan="3">
            <b>Доходы</b>
        </td>
    </tr>


    {{#_detail}}
    <tr>

        <td>{{type}}</td>

        <td align="right">{{in}}</td>
        <td></td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">

        <td align="right">Итого:</td>

        <td align="right">{{tin}}</td>
        <td></td>
    </tr>
    <tr>

        <td colspan="3">
            <b>Расходы</b> 
        </td>
    </tr>


    {{#_detail2}}
    <tr>

        <td>{{type}}</td>

        <td align="right">{{out}}</td>
        <td></td>
    </tr>
    {{/_detail2}}
    <tr style="font-weight: bolder;">

        <td align="right">Итого:</td>

        <td align="right">{{tout}}</td>
        <td></td>
    </tr>
    <tr style="font-weight: bolder;">

        <td align="right">Баланс:</td>

        <td align="right">{{total}}</td>
        <td></td>
    </tr>

    <tr style="font-weight: bolder;">

        <td>Финансовые показатели</td>

        <td></td>
        <td></td>

    </tr>
    <tr>
        <td>Проход:</td>
        <td align="right"> {{tu}} </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="Выручка (отпускная цена на количество) минус переменные  застраты (себестоимость)"></i>
        </td>
    </tr>
    <tr>
        <td>Переменные затраты :</td>
        <td align="right"> {{tvc}} </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="Себестоимость"></i>
        </td>
    </tr>
    <tr>
        <td>Операционные расходы:</td>
        <td align="right">{{OP}}  </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="Расходы минус  переменные  расходы (себестоимость) "></i>
        </td>
    </tr>
    <tr>
        <td>Чистая прибыль:</td>
        <td align="right"> {{PR}} </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="проход минус  расходы"></i>
        </td>
    </tr>
    {{#isinv}}
    <tr>
        <td>Инвестиции:</td>
        <td align="right"> {{inv}} </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="ТМЦ  на  складе  и основные  фонды  на  балансе"></i>
        </td>
    </tr>
    <tr>
        <td>Отдача от инвестиций (ROI),%:</td>
        <td align="right"> {{ROI}} </td>
        <td>
            <i class="fa fa-info-circle  " data-toggle="tooltip" data-placement="top"
               title="Проход на  инвестиции"></i>
        </td>
    </tr>
    {{/isinv}}
</table>


