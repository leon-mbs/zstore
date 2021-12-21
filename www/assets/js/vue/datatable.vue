<template>
     Datatable
     <table class="table table-sm table-hover">
        <tr >
          <th  v-for="(header ) in init.headers" >  {{header.title}}  </th>
        </tr>
       <tr v-for="(item ) in items" >
          <td v-for="(header ) in init.headers" > {{item[header.field]}}  </td>
        </tr>   
     
     </table>
 
 
</template>

<script>
module.exports = {
         mounted: function(){
            this.refresh()   
        } 
           ,
         methods: {
             refresh:function(){
                  
               var getdata  = this.ondata( )
                       
                    
                 getdata.then(data => {
                     
                     this.items = data.items 
                       
                 })       
            }     
            
            
        } ,
       
         data: function() {
            return {
                items:[]
            }
        } ,
   
    props:[  'init' ,'ondata' ]
}
</script>

 