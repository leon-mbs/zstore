 <table class="table  table-sm">
     <tr><th colspan="2">{{itemname}}</th></tr>
 
     <tr><td>Артикул</td><td>{{item_code}}</td></tr>
     <tr><td>Штрих-код</td><td>{{bar_code}}</td></tr>
     <tr><td>Бренд</td><td>{{brand}}</td></tr>
     <tr><td>Категорiя</td><td>{{cat_name}}</td></tr>
     <tr><td>На складi</td><td>{{qty}}</td></tr>
     <tr><td>Цiна</td><td>{{price}}</td></tr>
     <tr><td colspan="2"><small>{{notes}}</small></td></tr>
     {{#image}}
     <tr><td colspan="2"><img style="height:128px" src="{{image}}"> </td></tr>
     {{/image}}
 
 
   

 </table>