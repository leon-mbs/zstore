<table border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="6">
            Движение  по личному счету
        </td>
    </tr>
    <tr>

        <td align="center" colspan="6">
            Период с {{mfrom}} {{yfrom}} по {{mto}} {{yto}}
        </td>
    </tr>
  
    <tr>

        <td style="font-weight: bolder;" colspan="6">
            {{emp_name}}
        </td>
    </tr>
   
    <tr>

        <th>Дата</th>
        <th class="text-right">Начало</th>
        <th class="text-right">Добавлено</th>
        <th class="text-right">Вычтено</th>
        <th class="text-right">Конец</th>
        <th >Документ</th>

    </tr>
    {{#_detail}}

   <tr>

        <td>{{dt}}</td>
        <td class="text-right">{{begin}}</td>
        <td class="text-right">{{in}}</td>
        <td class="text-right">{{out}}</td>
        <td class="text-right">{{end}}</td>
        <td >{{doc}}</td>

    </tr>
    {{/_detail}}
   

</table>


