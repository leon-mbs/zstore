<table class="ctable"  >


    <tr>
        <td></td>
        <td>Постачальник</td>
        <td colspan="9">{{customer_name}}</td>
    </tr>
      <tr>
        <td colspan="11">{{{notes}}}</td>
    </tr>

    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="11" valign="middle">
            <br> Заявка № {{document_number}} від {{date}} <br><br>
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Найменування</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Артикул</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Код у пост.</th>

        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Штрих-код</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Бренд</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;">Од.</th>
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;"> </th>

        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Кіл.</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Ціна</th>
        <th style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;"  >Сума</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td>{{itemname}}</td>
        <td>{{itemcode}}</td>
        <td>{{custcode}}</td>
      
        <td>{{barcode}}</td>
        <td>{{brand}}</td>
        <td>{{msr}}</td>
        <td valign="top">{{desc}}</td>
        
        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="10" align="right">Разом:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>


</table>

