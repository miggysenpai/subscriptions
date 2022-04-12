<?php if(count(get_included_files()) ==1) die(); //Direct Access Not Permitted
if(pluginActive('membership',true)){
$memSettings = $db->query("SELECT * FROM plg_mem_settings")->first();
?>
<div class="form-group">
  <button type="button" onclick="window.location.href = '../usersc/membership.php';" name="button" class="btn btn-primary btn-block">Manage Membership</button>
</div>
<?php 
}
?>
