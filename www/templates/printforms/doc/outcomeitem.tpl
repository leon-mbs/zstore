<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="6" align="center">
            <b>Списання ТМЦ № {{document_number}} від {{date}}</b> <br>
        </td>
    </tr>
    <tr>
        <td><b>Зі складу:</b></td><td colspan="5">{{from}}</td>

    </tr>
   {{#storeemp}}
    <tr>
        <td><b>Зі співробітника:</b></td><td colspan="5">{{storeemp}}</td>
    </tr>
 
    {{/storeemp}}     
   {{#customer}}
    <tr>
        <td><b>Партнер:</b></td><td colspan="5">{{customer}}</td>
    </tr>
 
    {{/customer}}    
       



    <tr style="font-weight: bolder;">

        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Назва</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;"></th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>


        <th align="right"   style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Кіл.</th>
        <th align="right"   style="border-top:1px #000 solid;border-bottom:1px #000 solid;">На суму</th>

    </tr>
    {{#_detail}}
    <tr>

        <td>{{item_name}}</td>
        <td data-type="s">{{item_code}}</td>

        <td align="right" data-type="s">{{snumber}}</td>
        <td>{{msr}}</td>
        <td align="right">{{quantity}}</td>
        <td align="right">{{sum}}</td>

    </tr>
    {{/_detail}}
    <tr>
        <td align="right" colspan="5"><b>Всього:</b></td>
          <td align="right">{{amount}}</td>
    </tr>   
    
    
    <tr>
        <td colspan="6">{{{notes}}}</td>
    </tr>    
</table>



