<div id="shopcontent">



<?php
$settings = new PayqrSettingsHtml();
$html = $settings->getHtml();
echo $html;
?>
    
<style>
        .children
{
  display: none;
}
.base_options
{
  display: block;
}
</style>


<script>
  $("li.row a").click(function(){
    var id = "#child_" + $(this).parent().attr("id");
    $(id).toggle();
  });
  $("li.row select").change(function(){
    var id = "#child_" + $(this).parent().attr("id");
    var val = $(this).val();
    if(val == 1){
      $(id).show();
    }
    else {
      $(id).hide();
    }
  });
  $("li.row select").each(function(){
    var id = "#child_" + $(this).parent().attr("id");
    var val = $(this).val();
    if(val == 1){
      $(id).show();
    }
  });
</script>