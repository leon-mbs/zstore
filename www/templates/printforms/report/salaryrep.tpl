<table border="0" class="ctable" cellpadding="2" cellspacing="0">

    <tr style="font-size:larger; font-weight: bolder;">
        <td align="center" colspan="3">
            Звіт по заробітній платі
        </td>
    </tr>
    <tr>

        <td align="center" colspan="3">
            Перiод з {{mfrom}} {{yfrom}} по {{mto}} {{yto}}
        </td>
    </tr>
    {{#isemp}}
    <tr>

        <td style="font-weight: bolder;" colspan="3">
            {{emp_name}}
        </td>
    </tr>
    {{/isemp}}

    {{#_detail}}
    <tr>

        <td>{{k}}</td>
        <td class="text-end">{{v}}</td>
        <td></td>

    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">

        <td class="text-end">Всього:</td>

        <td class="text-end">{{total}}</td>
      <td></td>
    </tr>
     <tr >

        <td style="font-weight: bolder;" colspan="3">
         <br>   Нарахування та утримання
        </td>
    </tr>
    {{#_detail2}}
    <tr>

        <td>{{code}}</td>
        <td>{{name}}</td>
        <td class="text-end">{{am}}</td>

    </tr>
    {{/_detail2}}    
    
    
</table>


