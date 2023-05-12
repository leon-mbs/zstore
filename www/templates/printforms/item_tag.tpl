<div style="border: 1px solid   #ccc;;">
    <table class="ctable" border="0" cellpadding="1" cellspacing="0" style="width:100%"  > 
     
       
            <tr  >
                <td colspan="2"  style="font-size:24px"><b> {{name}}</b></td>
            </tr>
       
            <tr  >
                <td style="font-size:24px" > {{article}} &nbsp; &nbsp;</td>
               <td align="right" style="font-size:28px">
                  {{^isaction}}
                  <b  >{{price}}</b> 
                  {{/isaction}}
                  {{#isaction}}
                  <s style="font-size:smaller;" >{{price}}</s>&nbsp;  
                  
                    <b style="color:red;"  >{{actionprice}}</b>  
           
                    <b  >{{actionprice}}</b>  
                   
                  
                  {{/isaction}}
                </td>
                
            </tr>
           
            <tr style="font-size:18px">
                <td align="center" colspan="2"> <img style="width:100px" {{{barcodeattr}}}  >
                 <br>{{barcodewide}}</td>
            </tr>

       
    
    </table>
</div>   