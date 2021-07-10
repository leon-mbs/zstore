<div style="border: 1px solid   #ccc;;">
    <table class="ctable" border="0" cellpadding="1" cellspacing="0"  {{{style}}}  > 
       <tr><td>{{{qrcode}}}</td>
       <td>
           <table>
            <tr  >
                <td colspan="2"  {{{fsize}}} ><b> {{name}}</b></td>
            </tr>
            {{#isap}}
            <tr  >
                <td {{{fsize}}} > {{article}} </td>
                <td align="right"><b  {{{fsizep}}} >{{price}}</b></td>
            </tr>
            {{/isap}}
            <tr style="font-size:18px">
                <td align="center" colspan="2"> {{{img}}}<br>{{barcode}}</td>
            </tr>

          </table>
      </td></tr>
    </table>
</div>   