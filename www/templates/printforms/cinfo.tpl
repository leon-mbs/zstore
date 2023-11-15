 <table class="table  table-sm">
   <tr><td>Назва</td><td>{{name}}</td></tr>
   <tr><td>Тел.</td><td>{{phone}}</td></tr>
   {{#smscode}} 
   <tr><td colspan="2">   СМС код &nbsp;&nbsp;<b>{{smscode}}   </b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a {{{click}}} href="javascript:void(0);return false;">Відправити</a>   
   <br><small> Відправка коду на телефон для перевірки номеру   </small>
   </td></tr>
  {{/smscode}}
   
   {{#email}}
     <tr><td>E-mail</td><td>{{email}}</td></tr>
   {{/email}}  
   {{#address}}   
     <tr><td>Адреса</td><td>{{address}}</td></tr>
   {{/address}}   
      
   {{#bonus}}
   <tr><td>Бонуси</td><td>   {{bonus}}</td></tr>
   {{/bonus}}
  {{#dolg}}
   <tr><td>Борг  </td><td>   {{dolg}} <small>(+дебет -кредит)</small></td></tr>
   {{/dolg}}
  {{#disc}}
   <tr><td>Постійна знижка</td><td>   {{disc}}</td></tr>

   {{/disc}}
   <tr><td>Покупок на  суму</td><td>   {{sumall}}</td></tr>
   
   {{#comment}}   
   <tr><td colspan="2">Примітка: {{comment}}</td></tr>
   {{/comment}}    
   
   {{#last}}
     <tr><td colspan="2"> Останній документ: {{last}} від {{lastdate}} на суму  {{lastsum}}. Статус {{laststatus}}</td></tr>
     <tr><td colspan="2"> Останні товари: <td></tr>
     <tr><td colspan="2"> 
         <table      style="font-size:smaller">
            {{#goods}}
             <tr><td >{{itemname}} </td><td class="text-nowrap  ">{{item_code}}</td></tr>
            {{/goods}}
         
         </table>
       <td></tr>
      
      
     
   {{/last}}
   

 </table>