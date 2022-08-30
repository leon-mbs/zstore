<template>
  <div class="Typeahead">
 
 
    <input type="text"
           class="form-control"
           :placeholder="placeholder"
           autocomplete="off"
           v-model="query"
           @keydown.down="down"
           @keydown.up="up"
           @keydown.enter="hit"
           @keydown.esc="reset"
           @blur="reset"
           @input="update" />

    <ul    v-show="hasItems">
      <li v-for="(item, $item) in items" :class="activeClass($item)" @mousedown="hit" @mousemove="setActive($item)">
        <span class="name" v-text="item.value"></span>
        <span class="screen-name" v-text="item.screen_name"></span>
      </li>
    </ul>
  </div>
</template>



<script>
 
module.exports = {
 
   
  
  data () {
    return {
      items: [],
      query: '',
      current: -1,
       
      selectFirst: false,
      id:-1,name:''
     
      
    }
  },
         watch:   { 
             
                id(newVal, oldVal) { 
                    this.current = -1;
                    this.onhit(newVal);
                     
                  
                }   
                
           }  
         ,   
  computed: {
    hasItems () {
      return this.items.length > 0
    },

    isEmpty () {
      return !this.query
    }  ,
    
    myclass(){
       return this.class  
    }
  },                

  methods: {
    update () {
      //this.cancel()
      
      if (!this.query) {
        return this.reset()
      }
      
      if (  this.query.length < (this.minchars ? this.minchars :2)) {
        return
      }

      
      var getdata = this.onquery(this.query)
        getdata.then(data => {
             
              this.items = this.limit ? data.slice(0, this.limit) : data.slice(0, 8) 
              this.current = -1
              

              if (this.selectFirst) {
                this.down()
              }
        })
 
    
      
    },
  
   
    
    reset () {
      this.items = []
    //  this.query = ''
      
    },

    setActive (index) {
      this.current = index
    },

    activeClass (index) {
      return {
        active: this.current === index
      }
    },

    hit () {
      if (this.current !== -1) {
          var item = this.items[this.current]
          this.query = item.value
          this.reset()
          //this.onhit(item.id);
          this.id= item.key
      }
    },

    up () {
      if (this.current > 0) {
        this.current--
      } else if (this.current === -1) {
        this.current = this.items.length - 1
      } else {
        this.current = -1
      }
    },

    down () {
      if (this.current < this.items.length - 1) {
        this.current++
      } else {
        this.current = -1
      }
    } 
  }   
  ,
  props:['placeholder','onhit','onquery','limit','minchars'  ]
}
</script>



<style scoped>
.Typeahead {
  position: relative;
}
 
 
ul {
  position: absolute;
  padding: 0;
  margin-top: 8px;
  min-width: 100%;
  background-color: #fff;
  list-style: none;
  border-radius: 4px;
  box-shadow: 0 0 10px rgba(0,0,0, 0.25);
  z-index: 1000;
}
li {
  padding: 6px 12px;
  border-bottom: 1px solid #ccc;
  cursor: pointer;
}
li:first-child {
  border-top-left-radius: 4px;
  border-top-right-radius: 4px;
}
li:last-child {
  border-bottom-left-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom: 0;
}
span {
  display: block;
  color: #2c3e50;
}
span {
  display: block;
  color: #2c3e50;
}
.active {
  background-color: #3aa373;
}
.active span {
  color: white;
}
.name {
  font-weight: 400;
  font-size: 16px;
}
.screen-name {
  font-style: italic;
}
</style>
