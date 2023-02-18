<template>
<nav  >
  <ul class="pagination">
    
    <li  v-for="btn in buttons" v-bind:class="btn.class">
    
        <a  v-on:click.prevent="onbtn(btn.pageno)" class="page-link" href="void(0);">{{btn.title}}</a>
        
    </li>
    
  </ul>
 
</nav>
</template>
    
<script>
module.exports = {
         watch:   { 
              rows(newVal, oldVal) { 
                   if(newVal==0) this.currentpage=1;
                
                   this.refresh()
                  
                } ,
                reset(newVal, oldVal) { 
                    
                    this.onbtn(1)
                  
                } 
           }  
         ,   
         methods: {
            refresh:function(){
                 
                  this.buttons= []
                  if(this.pagesize == undefined)  this.pagesize = 25  
                  if(this.buttonscnt == undefined)  this.buttonscnt = 10  
                  var pages = Math.ceil(this.rows / this.pagesize)
                  
                  if( pages < 2 ) return;
                  if( pages < this.currentpage ) this.currentpage =1;

                  var i
                  var iLeft = Math.floor(this.buttonscnt / 2)
                  var iRight =  iLeft 
                   
                  if(pages <= iRight + iRight + 1){
                      for (i = 1; i <= pages; i++) {
                           if (this.currentpage == i) {
                                this.buttons.push({"pageno":i,"title":i,class:"page-item active"}) 
                           }else {
                                this.buttons.push({"pageno":i,"title":i,class:"page-item "}) 
                           
                           }
                      
                      }
                  } else {
                         if (this.currentpage > iLeft && this.currentpage < (pages - iRight)) {
                            
                            this.buttons.push({"pageno":1,"title":"<<",class:"page-item "}) 
     
                            for (i = this.currentpage - iLeft; i <= this.currentpage + iRight; i++) {

                                if (this.currentpage == i) {
                                    this.buttons.push({"pageno":i,"title":i,class:"page-item active"}) 
                                    
                                } else {
                                    this.buttons.push({"pageno":i,"title":i,class:"page-item "}) 
                                }

                            }
                            this.buttons.push({"pageno":pages,"title":">>",class:"page-item "}) 
                            

                        } else if (this.currentpage <= iLeft) {

                            var iSlice = 1 + iLeft - this.currentpage;
                            for (i = 1; i <= this.currentpage + (iRight + iSlice); i++) {
                                if (this.currentpage == i) {
                                       this.buttons.push({"pageno":i,"title":i,class:"page-item active"}) 
  
                                } else {
                                       this.buttons.push({"pageno":i,"title":i,class:"page-item "}) 
                                }

                            }
                            
                              this.buttons.push({"pageno":pages,"title":">>",class:"page-item "}) 
 
                        } else {
                            this.buttons.push({"pageno":1,"title":"<<",class:"page-item "}) 
                           

                            var iSlice = iRight - (pages - this.currentpage);

                            for (i = this.currentpage - (iLeft + iSlice); i <= pages; i++) {
                                if (this.currentpage == i) {
                                    
                                    this.buttons.push({"pageno":i,"title":i,class:"page-item active"}) 
                                      
                                } else {
                                   this.buttons.push({"pageno":i,"title":i,class:"page-item "}) 
  
                                }
                            }

                        }   
                   }
         
                    
            } ,
            
            onbtn:function(i){
              this.currentpage=i;
              this.refresh()    
 this.$emit('onpage',i)
//              this.onpage(i)

            }   
        } ,
       
         data() {
            return {
                reset:0,
                currentpage:1,
             
                buttons: []
            }
        } ,
   
    props:['rows' ,'pagesize','buttonscnt'  ]
}
</script>

 