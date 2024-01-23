<table  class="ctable"  >
 
    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="2">
            Управлiнський  баланс
        </td>
    </tr>
    <tr>
    <tr>

        <td align="center" colspan="2">
            На {{datefrom}}  <br>
        </td>
    </tr>
 
 
   <tr>

        <td colspan="2">            <b>Активи</b>         </td>
    </tr>
    {{#amat}}
    <tr>
        <td > Матерiали та  комплектуючi        </td>
        <td align="right"> {{amat}}  </td>
    </tr>
    {{/amat}}
    <tr>
        <td > Напiвфабрикати та  готова  продукцiя        </td>
        <td align="right">                    </td>
    </tr>
    <tr>
        <td >   МШП        </td>
        <td align="right">          </td>
    </tr>
    <tr>
        <td >Товари        </td>
        <td align="right">        </td>
    </tr>
    <tr>
        <td >  Iншi ТМЦ        </td>
        <td align="right">                   </td>
    </tr>
    <tr>
        <td >  Готiвка        </td>
        <td align="right">                   </td>
    </tr>
    <tr>
        <td >  Безготiвка        </td>
        <td align="right">                   </td>
    </tr>
    <tr>
        <td >  Дебетовий борг (постачальники)        </td>
        <td align="right">                   </td>
    </tr>
    <tr>
        <td >  Дебетовий борг (покупцi)        </td>
        <td align="right">                   </td>
    </tr>
    <tr>
        <td >  Спiвробiтники (виданi  аванси тощо)        </td>
        <td align="right">                   </td>
    </tr>
    
    
     <tr style="font-weight: bolder;">
        <td align="right">Всього:</td>
        <td align="right">{{atotal}}</td>
    </tr>   
    
   <tr>
        <td colspan="2">     <b>Пасиви</b>        </td>    </tr>

    <tr>
        <td >  Кредитовий  борг (постачальники)        </td>
        <td align="right">                   </td>
    </tr>
    <tr>
        <td >  Кредитовий  борг (покупцi)        </td>
        <td align="right">                   </td>
    </tr>
    <tr>
        <td >  Спiвробiтники (зарплата до видачi тощо)        </td>
        <td align="right">                   </td>
    </tr>


     <tr style="font-weight: bolder;">
        <td align="right">Всього:</td>
        <td align="right">{{ptotal}}</td>
    </tr>   
     <tr style="font-weight: bolder;">
        <td align="right">Баланс:</td>
        <td align="right">{{bal}}</td>
    </tr>   
    
</table>


