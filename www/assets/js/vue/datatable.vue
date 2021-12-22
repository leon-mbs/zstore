<template>
     <div>
     <table class="table table-sm table-hover">
        <tr >
          <th  v-for="(header ) in headers" :class="header.thclass" >
            
            <span v-if="header.sortable" v-on:click="sortclick(header.field)" style="cursor:pointer">
               <span v-text="header.title"></span> 
               <span v-show="sort=='asc'  && header.field == sortfield" >&#8595;</span> 
               <span v-show="sort=='desc' && header.field == sortfield" >&#8593;</span> 
         
            </span> 
            <span v-else v-text="header.title"> </span> 
            
            </th>
        </tr>
        
             
       <tr v-for="(item ) in items" >
          <td v-for="(header ) in headers" :class="header.tdclass"   >
          
                    <span v-if="header.clickable" v-on:click="click(header.field,item)" style="cursor:pointer" v-html="item[header.field]"></span>  
                    <span v-else v-html="item[header.field]"></span>  
          </td>
        </tr>   
        <tr v-show="pagination" ><td :colspan="headers.length">
        <ul class="pagination">
                   
        </ul>
        </td></tr>
     </table>
     </div>
      
</template>

<script>
module.exports = {
         mounted: function(){
            this.headers = this.init.headers 
            this.pagination = this.init.pagination 
            
            this.refresh()   
        } 
           ,
         methods: {
             refresh:function(){
               this.isloading=true;   
               var getdata  = this.ondata(this.sortfield, this.sort)
                       
                    
                 getdata.then(data => {
                     
                     this.items = data.items 
                     this.isloading=false;     
                 })       
            }     
            ,click:function (field,item){
                
            }
            ,sortclick:function (field ){
                if(this.sort=='asc') this.sort='desc'
                else   this.sort='asc'
               
                this.sortfield = field;
                this.refresh();
               // this.$forceUpdate();
                
            }
            
        } ,
       
         data: function() {
            return {
                items:[] ,
                headers:[]  ,
                sortfield:'' ,
                sort:'' ,
                pagination:false ,
                isloading:false
            }
        } ,
   
    props:[  'init' ,'ondata'   ]
}
</script>

 