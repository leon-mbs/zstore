<table class="ctable" border="0" cellspacing="0" cellpadding="2">
    <tr>
        <td colspan="4" align="center">
            <b> Перекомплектації ТМЦ № {{document_number}} від {{date}}</b> <br>
        </td>
    </tr>
    <tr>
        <td><b>Зі складу:</b></td><td colspan="3">{{from}}</td>
     

    </tr>
{{#fromlist}}
  <tr>
        <td>
           {{fromname}} 
        </td>
        <td data-type="s">
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
        <td><b>На склад:</b></td><td colspan="3">{{to}}</td>
     

    </tr>    
 {{#tolist}}
  <tr>
        <td>
           {{toname}} 
        </td>
        <td data-type="s">
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


