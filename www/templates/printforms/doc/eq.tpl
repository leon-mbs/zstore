<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="4" align="center">
            <b> {{opname}} № {{document_number}} від {{document_date}}</b> <br>
        </td>
    </tr>
  
 
    <tr>
        <td><b>Найменування:</b></td><td>{{eqname}}</td>
        <td><b>Інв. номер:</b></td><td>{{invnumber}}</td>
   </tr> 
 

   {{#isamount }}
    <tr>
        <td><b>Сума:</b></td><td colspan="3">{{amount }}</td>
    </tr>
   {{/isamount }}   
   {{#iscust }}
    <tr>
        <td><b>Контрагент:</b></td><td colspan="3">{{customer_name }}</td>
    </tr>
   {{/iscust }}   
 
   {{#ispa}}
    <tr>
        <td><b>Виробнича дільниця:</b></td><td colspan="3">{{pa_name}}</td>
    </tr>
   {{/ispa}}
   {{#isemp}}
    <tr>
        <td><b>Відповідальний:</b></td><td colspan="3">{{emp_name}}</td>
    </tr>
   {{/isemp}}
   {{#isitem }}
    <tr>
        <td><b>ТМЦ:</b></td><td>{{item_name}}</td><td><b>Склад:</b></td><td>{{store_name}}</td>
 </tr>
  {{/isitem }}
    <tr>
        <td colspan="4">
            {{{notes}}}
        </td>
    </tr>
     

    <tr>    
        <td colspan="4" > 
        <br>    Підпис ___________
        </td>
        

    </tr>

</table>


