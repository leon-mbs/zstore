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
   
    {{#aprod}}    
    <tr>
        <td > Напiвфабрикати та  готова  продукцiя        </td>
        <td align="right">  {{aprod}}      </td>
    </tr>
    {{/aprod}} 
    {{#ambp}}      
    <tr>
        <td >   МШП        </td>
        <td align="right">     {{ambp}}               </td>
    </tr>
    {{/ambp}}   

    {{#aitem}}                 
    <tr>
        <td >Товари        </td>
        <td align="right">    {{aitem}}      </td>
    </tr>
    {{/aitem}}                 
    {{#aother}}                 
    <tr>
        <td >  Iншi ТМЦ    </td>
        <td align="right">     {{aother}}     </td>
    </tr>
    {{/aother}}                     
    {{#anal}}                 
    <tr>
        <td >  Готiвка        </td>
        <td align="right">   {{anal}}                        </td>
    </tr>
    {{/anal}}                     
    {{#abnal}}                 
    <tr>
        <td >  Безготiвка        </td>
        <td align="right">  {{abnal}}   </td>
    </tr>
    {{/abnal}}                     
    {{#debet}}
    <tr>  
        <td >Борг  контрагентiв       </td>
        <td align="right">     {{debet}}              </td>
    </tr>
    {{/debet}} 
    {{#aemp}}                 
    <tr>
        <td >  Спiвробiтники (виданi  аванси тощо)        </td>
        <td align="right">        {{aemp}}             </td>
    </tr>
    {{/aemp}}                 
   {{#aeq}}                 
    <tr>
        <td >  Балансова вартiсть ОЗ        </td>
        <td align="right">        {{aeq}}             </td>
    </tr>
    {{/aeq}}                 
    
    
     <tr style="font-weight: bolder;">
        <td align="right">Всього:</td>
        <td align="right">{{atotal}}</td>
    </tr>   
    
   <tr>
        <td colspan="2">     <b>Пасиви</b>        </td>    </tr>

    {{#credit}}
    <tr>  
        <td >  Борг  контрагентам       </td>
        <td align="right">     {{credit}}              </td>
    </tr>
    {{/credit}} 
 
    {{#pemp}}                 
    <tr>
        <td >  Спiвробiтники (зарплата до видачi тощо)        </td>
        <td align="right">    {{pemp}}               </td>
    </tr>
     {{/pemp}}

     <tr style="font-weight: bolder;">
        <td align="right">Всього:</td>
        <td align="right">{{ptotal}}</td>
    </tr>   
     <tr >
        <td ></td>
        <td ></td>
    </tr>   
     <tr style=";font-weight: bolder;">
        <td align="right">Баланс:</td>
        <td align="right">{{bal}}</td>
    </tr>   
    
</table>


