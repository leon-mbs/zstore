<template>
  <select  @change="$emit('input', event.target.value)"  v-model="value" :name="name" :id="id" class='form-control' >

       <option v-for="opt in options"  :value="opt.key">{{opt.value}}</option>
       
   </select>
</template>



<script>
module.exports = {

    mounted: function() {
          var vm = this;
               setTimeout(() => { 
               $(vm.$el).select2({theme:'bootstarp4'})
               .on("change", function() {
               vm.$emit("input", this.value);
                });
            }, 250 );  
            
        },
 
        
 
   
        updated: function() {
             var vm = this;
                setTimeout(() => { 
               
                     $(vm.$el) 
                    .off()
                    .select2("destroy");
                   
                       $(vm.$el).select2({theme:'bootstarp4'})
                       .on("change", function() {
                       vm.$emit("input", this.value);
                    });    
                   
                   
                }, 250 );  
                 
        },
          
        props: ["options", "value", "id","name" ],
}
</script>