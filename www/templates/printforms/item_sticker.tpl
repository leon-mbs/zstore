    <div style="{{turn}}border: 1px solid   #ccc;width:100%;;">
    <table class="ctable" border="0" cellpadding="1" cellspacing="0" style="width:100%"  > 
       <td>
           <table>
            <tr  >
                <td   colspan="3"  ><b> {{name}}</b></td>
            </tr>
           <tr  >
                <td colspan="3"  >Код {{code}}</td>
            </tr>
            <tr  >
                <td  >Вага</td>
                <td  >Ціна</td>
                <td style="font-size:18px" ><b> Сума</b></td>
            </tr>
            <tr  >
                <td  >Кіл.</td>
                <td  >Ціна</td>
                <td  ><b> Сума</b></td>
            </tr>
           {{#isbarcode}}
            <tr  >
                <td align="center" colspan="3">
                <img style="width:80%" {{{barcode}}}  >
                
            </tr>
            {{/isbarcode}}  
          
     
            
          </table>
      </td></tr>
    </table>
</div>   