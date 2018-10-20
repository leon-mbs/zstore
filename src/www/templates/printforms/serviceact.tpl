 
        <table class="ctable"   border="0" cellpadding="2" cellspacing="0">
            <tr>
                <td width="100">
                    Заказчик:
                </td>
                <td>
                    {{customer}}
                </td>
                <td>
                    Дата: {{date}}
                </td>
            </tr>

            <tr style="font-weight: bolder;">
                <td colspan="3" align="center">
                    Акт выполненых работ № {{document_number}}
                </td>
            </tr>

        </table>
        <br>
        <table class="ctable" width="600" cellspacing="0" cellpadding="1" border="0">
            <tr style="font-weight: bolder;">
                <th width="20" style="border: 1px solid black;">№</th>
                <th style="border: 1px solid black;" width="180">Наименование</th>
                
                <th style="border: 1px solid black;" width="50" align="right">Кол.</th>
                <th style="border: 1px solid black;" width="50" align="right">Цена</th>
                <th style="border: 1px solid black;" width="50" align="right">Сумма</th>
            </tr>
            {{#_detail}}
            <tr>
                <td>{{no}}</td>
                <td>{{servicename}}</td>
            
                <td align="right">{{quantity}}</td>
                <td align="right">{{price}}</td>
                <td align="right">{{amount}}</td>
            </tr>
            {{/_detail}}
            <tr style="font-weight: bolder;">
                <td colspan="4" style="border-top: 1px solid black;" align="right">Всего:</td>
                <td style="border-top: 1px solid black;" align="right">{{total}} </td>
            </tr>
           

        </table>

 
