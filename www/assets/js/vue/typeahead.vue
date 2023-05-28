<template>
 
 
    <input type="text"
           class="form-control"
           :placeholder="placeholder"
           autocomplete="off"
           v-model="query"
           :name="name" 
           :id="id"
       />
    
</template>



<script>
 
module.exports = {
  
  data () {
    return {
      
      query: '',
      selected: this.value,
      
    }
  },
     watch:   { 
                
             value(newVal, oldVal) { 

                if(newVal==0) {
                  this.query=""
                }   
             }    
               
       }  
     ,   
                
 
  
    mounted: function() {
          var vm = this;
           
               $(vm.$el).typeahead({
                   minLength: vm.minchars ? vm.minchars :2 ,
                 
                   source: function (query, process) {

                   var getdata = vm.onquery(query)
                   
                   getdata.then(data => {
                         
                        var items = vm.limit ? data.slice(0, vm.limit) : data.slice(0, 8) 
                        
                        var it=[]
                        for(let i of items){
                           it.push(i.key +'_'+i.value)    
                        }
                          
                        process(it)
                         
                   })
                 
                      
                   },
                   highlighter :function(item) {   
                 var parts = item.split('_');
                          parts.shift(); 
                          return parts.join('_');
                     
                   }    ,               
                                 
                   updater :function(item) {   
                    var parts = item.split('_');
                       vm.selected = parts.shift(); 
                          
                          return parts.join('_');
                     
                   }    ,               
                                 
                   afterSelect :function(item) {  
                       vm.$emit("input", vm.selected);                 
                       
                   }                   
               })
        
            
        },  
  
  
  props:['placeholder', 'value', 'onquery','limit','minchars',"id","name"  ]
}
</script>


 
 