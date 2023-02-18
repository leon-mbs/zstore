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
             <li  v-for="btn in buttons" v-bind:class="btn.class">
        
               <a  v-on:click.prevent="onbtn(btn.pageno)" class="page-link" href="void(0);">{{btn.title}}</a>
            
           </li>              
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
             if(Number.isInteger(this.init.pagesize)) this.pagesize = this.init.pagesize 
             if(Number.isInteger(this.init.buttonscnt)) this.buttonscnt = this.init.buttonscnt 
            
            
            this.refresh()   
        } 
           ,
         methods: {
             refresh:function(){
               this.isloading=true; 
               var getdata
               if(this.pagination)    {
                  getdata  = this.ondata(this.currentpage-1,this.pagesize,this.sortfield, this.sort)    
               } else {
                  getdata  = this.ondata(-1,-1,this.sortfield, this.sort)    
               }
               
               
                       
                    
                 getdata.then(data => {
                     
                     this.items = data.items 
                     this.isloading=false      
                     this.allrows=data.allrows       
                     this.renderpag()       
                 })       
            }     
            ,click:function (field,item){
                
            }
            ,sortclick:function (field ){
                if(this.sort=='asc') this.sort='desc'
                else   this.sort='asc'
               
                this.sortfield = field;
                this.currentpage=1;
                
                this.refresh();
               // this.$forceUpdate();
                
            }
        ,
            
            onbtn:function(i){
              this.currentpage=i;
              this.refresh()    
               
            } ,
            
            renderpag:function(){
                  this.buttons= []
                  
                  var pages = Math.ceil(this.allrows / this.pagesize)
                  
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
           
               
            }  
            
            
                 
        } ,
       
         data()  {
            return {
                items:[] ,
                headers:[]  ,
                sortfield:'' ,
                sort:'' ,
                pagination:false ,
                isloading:false,
                currentpage:1,
                buttons: [] ,
                pagesize:25 ,
                buttonscnt:10 ,
                allrows:0 
                 
                               
            }
        } ,
   
    props:[  'init' ,'ondata'   ]
}
</script>

 