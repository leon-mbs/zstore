    <div style="{{turn}}border: 1px solid   #ccc;width:100%;;">
    <table class="ctable" border="0" cellpadding="1" cellspacing="0" style="width:100%"  > 
       <td>
           <table>
            <tr  >
                <td colspan="2"  style="font-size:24px"><b> {{name}}</b></td>
            </tr>
           
            <tr  >
                <td align="left" style="font-size:50px" >
               
                  Код  {{article}}  
                 
                 
                 </td>
                <td   style="font-size:50px">
                {{#isterm}}
                   Придатний до  {{term}}  
                {{/isterm}}
                </td>
                
            </tr>
          

        
            <tr style="font-size:18px">
                <td align="center" colspan="2">
                <img style="width:80%" {{{barcodeattr}}}  >
                
            </tr>
                   

     
            
          </table>
      </td></tr>
    </table>
</div>   