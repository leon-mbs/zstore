<table class="ctable" border="0" cellpadding="1" cellspacing="0" {{{printw}}}>
    <tr>
        <td align="center" colspan="2"> <b>Х-Звiт </b></td>
    </tr>
    <tr>

        <td  > Створено</td> <td  > {{created_at}}</td>
        
    </tr>   
   <tr>
        <td  > Чекiв</td>        <td  > {{cnt}}</td>
    </tr>    
    <tr>
        <td  > Готiвка</td>        <td  > {{nal}}  {{#isrnal}} (повернення {{rnal}})  {{/isrnal}}   </td>
    </tr>
   <tr>
        <td  > Картка</td>        <td  > {{card}}  {{#isrcard}} (повернення {{rcard}})  {{/isrcard}}</td>
    </tr>
  <tr>
        <td  > <b>Всього</b></td>        <td  > <b>{{total}}</b></td>
    </tr>
 
 

</table>
<br>