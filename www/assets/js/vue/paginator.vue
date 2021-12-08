<template>
<nav  >
  <ul class="pagination">
    <li class="page-item "><a class="page-link" href="#">Previous</a></li>
    <li class="page-item"><a class="page-link" href="#">1</a></li>
    <li class="page-item"><a class="page-link" href="#">2</a></li>
    <li class="page-item"><a class="page-link" href="#">3</a></li>
    <li class="page-item"><a class="page-link" href="#">Next</a></li>
  </ul>
</nav>
</template>

<script>
module.exports = {
    data: function() {
        return {
            who: 'world'
        }
    } ,
    props:['pr']
}
</script>

 