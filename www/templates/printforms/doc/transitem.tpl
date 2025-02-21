<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="4" align="center">
            <b> Перекомплектації ТМЦ № {{document_number}} від {{date}}</b> <br>
        </td>
    </tr>
    <tr>
        <td colspan="4">
            <b> Зі складу:</b>  {{from}}
        </td>
     

    </tr>
{{#fromlist}}
  <tr>
        <td>
           {{fromname}} 
        </td>
        <td>
          {{fromcode}}     
        </td>
       <td>
          {{fromqty}}      
        </td>
        <td>
          {{fromprice}}      
        </td>

    </tr> 
    {{/fromlist}}   

    <tr>
        <td colspan="4">
            <b> На склад:</b>   {{to}}
        </td>
     

    </tr>    
 {{#tolist}}
  <tr>
        <td>
           {{toname}} 
        </td>
        <td>
          {{tocode}}     
        </td>
       <td>
          {{toqty}}      
        </td>
        <td>
          {{toprice}}      
        </td>

    </tr> 
    {{/tolist}}   

  
    <tr>
        <td colspan="4">{{{notes}}}</td>
    </tr>


</table>


