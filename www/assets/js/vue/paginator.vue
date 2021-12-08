<template>
<nav  >
  <ul class="pagination">
    
    <li  v-for="item in items" class="page-item">
    
        <a v-if="item.link" v-on:click.prevent="onbtn(item.pageno)" class="page-link" href="#">{{item.pageno}}</a>
        <span v-if="item.nolink">...<span>
    </li>
    
  </ul>
 
</nav>
</template>

<script>
module.exports = {
         watch:   { 
              rows(newVal, oldVal) { 
                  
                   this.refresh()
                } 
           }  
         ,   
         methods: {
            refresh:function(){
                console.log(this.test);
                
            } ,
            
            onbtn:function(i){
              this.onpage(i)
            }   
        } ,
       
         data: function() {
            return {
                
         
             
                items: [{"pageno":1,"link":true,"nolink":false},{"pageno":2,"link":false,"nolink":true},{"pageno":3,"link":true,"nolink":false}]
            }
        } ,
   
    props:['rows' ,'pagesize','buttons','onpage' ]
}
</script>

 