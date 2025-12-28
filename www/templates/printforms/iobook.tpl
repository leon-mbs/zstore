 <table class="ctable" border="0"   cellpadding="2" cellspacing="0">
   <tr style="font-size:larger; font-weight: bolder;">
     <td colspan="11" align="center" > Книга облiку прибуткiв та видаткiв </td>
   </tr>
  <tr style=" font-weight: bolder;">
     <td colspan="8"   > {{firmname}} </td>
     <td colspan="3"   > {{firmcode}} </td>
   </tr>
 <tr style=" font-weight: bolder;">
     <td colspan="11" align="center"  > Перiод з {{from}}  по {{to}}   </td>
    
   </tr>
   <tr>
     <td valign="top"   style=" font-size: smaller;; border: solid black 1px" rowspan="2">Дата </td>
     <td valign="top"    style=" font-size: smaller; border: solid black 1px" rowspan="2">Сума доходу, отриманого від здійснення господарської діяльності або незалежної професійної діяльності </td>
     <td valign="top"    style=" font-size: smaller; border: solid black 1px" rowspan="2">Сума повернутих покупцям (замовникам) коштів та/або передплати за товари (роботи, послуги) 
</td>
     <td  valign="top"   style=" font-size: smaller; border: solid black 1px" rowspan="2">Загальна сума отриманого доходу, яка підлягає декларуванню  (гр. 2 – гр. 3) 
</td>
     <td valign="top"  align="center" style=" font-size: smaller; border: solid black 1px" colspan="6">Витрати, пов’язані з господарською діяльністю, які документально підтверджені</td>
 
     <td  valign="top"   style=" font-size: smaller; border: solid black 1px" rowspan="2"> Сума чистого оподаткованого доходу (гр. 4 – 6, 7, 8, 9, 10)
</td>
   </tr>
      <tr>
 
     <td  valign="top"   style=" font-size: smaller; border: solid black 1px">реквізити документа, що підтверджує понесені витрати
</td>
     <td  valign="top"   style=" font-size: smaller; border: solid black 1px">витрати на  придбання ТМЦ, що реалізовані/ використані у виробництві продукції, товарів (наданні робіт, послуг)
</td>
     <td  valign="top"  style=" font-size: smaller; border: solid black 1px">витрати на оплату праці фізичних осіб, що перебувають у трудових відносинах/за виконання робіт, послуг згідно з договорами цивільно-правового характеру</td>
     <td valign="top"   style=" font-size: smaller; border: solid black 1px">витрати зі сплати податків, зборів, єдиного внеску на загальнообов’язкове державне соціальне страхування,  платежів, за одержання ліцензій, дозволів  </td>
     <td  valign="top"  style=" font-size: smaller; border: solid black 1px">інші витрати, пов’язані з одержанням доходу </td>
     <td valign="top"   style=" font-size: smaller; border: solid black 1px">амортизаційні відрахування3
</td>
 
   </tr>   

    <tr> 
     <td style="text-align: center;border: solid black 1px">1</td>
     <td style="text-align: center;border: solid black 1px">2</td>
     <td style="text-align: center;border: solid black 1px">3</td>
     <td style="text-align: center;border: solid black 1px">4</td>
     <td style="text-align: center;border: solid black 1px">5</td>
     <td style="text-align: center;border: solid black 1px">6</td>
     <td style="text-align: center;border: solid black 1px">7</td>
     <td style="text-align: center;border: solid black 1px">8</td>
     <td style="text-align: center;border: solid black 1px">9</td>
     <td style="text-align: center;border: solid black 1px">10</td>
     <td style="text-align: center;border: solid black 1px">11</td>
   </tr> 
 
 
   {{#rows}}
    <tr > 
     <td valign="top"  >{{date}}</td>
     <td valign="top" style="text-align: right; ">{{c2}} </td>
     <td valign="top" style="text-align: right; ">{{c3}} </td>
     <td valign="top" style="text-align: right; ">{{c4}} </td>
     <td valign="top"  >{{dn}}    </td>
     <td valign="top" style="text-align: right; ">{{c6}}  </td>
     <td valign="top" style="text-align: right; ">{{c7}} </td>
     <td valign="top" style="text-align: right; ">{{c8}} </td>
     <td valign="top" style="text-align: right; ">{{c9}} </td>
     <td valign="top" style="text-align: right; ">{{c10}} </td>
     <td valign="top" style="text-align: right; ">{{c11}} </td>
   </tr>  
    {{/rows}}
      <tr style=" font-weight: bolder;"> 
     <td valign="top" style="text-align: right; "  >Всього:</td>
     <td valign="top" style="text-align: right; ">{{tc2}} </td>
     <td valign="top" style="text-align: right; ">{{tc3}} </td>
     <td valign="top" style="text-align: right; ">{{tc4}} </td>
     <td valign="top"  >     </td>
     <td valign="top" style="text-align: right; ">{{tc6}}  </td>
     <td valign="top" style="text-align: right; ">{{tc7}} </td>
     <td valign="top" style="text-align: right; ">{{tc8}} </td>
     <td valign="top" style="text-align: right; ">{{tc9}} </td>
     <td valign="top" style="text-align: right; ">{{tc10}} </td>
     <td valign="top" style="text-align: right; ">{{tc11}} </td>
   </tr> 
  </table>
  <br>