<html>
    <body>
        <table class="ctable" width="500px" border="0" cellpadding="2">
            <tr>

                <td colspan="2">
                    Дата: {{date}}
                </td>
            </tr>
            <tr>
                <td>
                    Со склада: {{from}}
                </td>

                <td>
                    На склад: {{to}}
                </td>
            </tr>
            <tr style="font-weight: bolder;">
                <td colspan="2" align="center">
                    Перемещение товара № {{document_number}}
                </td>
            </tr>

        </table>
        <br>
        <table class="ctable" width="500px" cellspacing="0" cellpadding="1" border="1">
            <tr style="font-weight: bolder;">
                <th width="20px">№</th>
                <th  >Название</th>
                 
                <th width="50px">Кол.</th>
                <th width="50px">Цена</th>
            </tr>
            {{#_detail}}
            <tr>
                <td>{{no}}</td>
                <td>{{item_name}}</td>
          
                <td align="right">{{quantity}}</td>
                <td align="right">{{price}}</td>
            </tr>
            {{/_detail}}
        </table>


        <br> <br>
    </body>
</html>
