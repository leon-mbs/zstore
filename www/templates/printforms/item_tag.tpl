<div style="border: 1px solid   #ccc;;">
    <table class="ctable" border="0" cellpadding="1" cellspacing="0" style="width:100%"  > 
       <tr><td>{{{qrcode}}}</td>
       <td>
           <table>
            <tr  >
                <td colspan="2"  style="font-size:24px"><b> {{name}}</b></td>
            </tr>
            {{#isap}}
            <tr  >
                <td style="font-size:24px" > {{article}} &nbsp; &nbsp;</td>
               <td align="right" style="font-size:28px">
                  {{^action}}
                  <b  >{{price}}</b> 
                  {{/action}}
                  {{#action}}
                  <s style="font-size:smaller;" >{{price}}</s>&nbsp;  
                   {{#iscolor}}
                    <b style="color:red;"  >{{actionprice}}</b>  
                   {{/iscolor}}
                   {{^iscolor}}
                    <b  >{{actionprice}}</b>  
                   {{/iscolor}}
                  
                  {{/action}}
                </td>
                
            </tr>
            {{/isap}}
            <tr style="font-size:18px">
                <td align="center" colspan="2"> {{{img}}}<br>{{barcode}}</td>
            </tr>

          </table>
      </td></tr>
    </table>
</div>   