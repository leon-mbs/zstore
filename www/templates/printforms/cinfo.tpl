 <table class="table  table-sm">
   <tr><td>Назва</td><td>{{name}}</td></tr>
   <tr><td>Тел.</td><td>{{phone}}</td></tr>
   <tr><td>E-mail</td><td>{{email}}</td></tr>
   <tr><td>Адреса</td><td>{{address}}</td></tr>
   {{#bonus}}
   <tr><td>Бонуси</td><td>   {{bonus}}</td></tr>
   {{/bonus}}
  {{#dolg}}
   <tr><td>Борг  </td><td>   {{dolg}} <small>(+дебет -кредит)</small></td></tr>
   {{/dolg}}
  {{#disc}}
   <tr><td>Знижка</td><td>   {{disc}}</td></tr>
   {{/disc}}

 {{#last}}
   <tr><td colspan="2"> Останній документ: {{last}} від {{lastdate}} на суму  {{lastsum}}. Статус {{laststatus}}</td></tr>
   {{/last}}
   
   <tr><td colspan="2">Примітка: {{comment}}</td></tr>
 

 </table>