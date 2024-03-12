    <div style="{{turn}}border: 1px solid   #ccc;width:100%;;">
    <table class="ctable" border="0" cellpadding="1" cellspacing="0" style="width:100%"  > 
       <td>
           <table>
            <tr  >
                <td colspan="2"  style="font-size:24px"><b> {{name}}</b></td>
            </tr>
           
            <tr  >
                <td align="left" style="font-size:20px" >
                 {{#isarticle}}
                     {{article}}  
                 {{/isarticle}}
                 
                 </td>
                <td align="right" style="font-size:28px">
                {{#isprice}}
                <b>  {{price}} </b>
                {{/isprice}}
                </td>
                
            </tr>
          

            {{#isbarcode}}
            <tr style="font-size:18px">
                <td align="center" colspan="2">
                <img style="width:80%" {{{barcodeattr}}}  >
                <br>{{barcode}}</td>
            </tr>
            {{/isbarcode}}            

            {{#isqrcode}}
               <tr><td align="center" colspan="2">
                <img style="width:80%" {{{qrcodeattr}}}  >
               
               </td> </tr>
            {{/isqrcode}}
            
          </table>
      </td></tr>
    </table>
</div>   