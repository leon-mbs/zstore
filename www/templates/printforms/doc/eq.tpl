<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="4" align="center">
            <b> {{opname}} № {{document_number}} від {{document_date}}</b> <br>
        </td>
    </tr>
  
 
    <tr>
        <td colspan="2">
            <b>Найменування:</b> {{eqname}}
        </td>
        <td colspan="2">
            <b>Інв. номер:</b> {{invnumber}}
        </td>
   </tr> 
 

   {{#isamount }}
    <tr>
        <td colspan="4">
          <b>Сума:</b>   {{amount }}
        </td>
    </tr>
   {{/isamount }}   
   {{#iscust }}
    <tr>
        <td colspan="4">
          <b>Контрагент:</b>   {{customer_name }}  
        </td>
    </tr>
   {{/iscust }}   
 
    <tr>
        <td colspan="4">
          {{#ispa}}
          <b>Виробнича дільниця:</b>   {{item_name}}   &nbsp;&nbsp;&nbsp;&nbsp;
          {{/ispa}} 
           {{#isemp}}
            <b>Відповідальний</b> {{store_name}}
          {{/isemp}} 
        </td>
    </tr>
   {{#isitem }}  
    <tr>
        <td colspan="4">
          <b>ТМЦ:</b>   {{item_name}}   <b>Склад</b> {{store_name}}
        </td>
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


