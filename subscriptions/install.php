<?php
require_once("init.php");
//For security purposes, it is MANDATORY that this page be wrapped in the following
//if statement. This prevents remote execution of this code.
if (in_array($user->data()->id, $master_account)){


$db = DB::getInstance();
include "plugin_info.php";

//all actions should be performed here.
$check = $db->query("SELECT * FROM us_plugins WHERE plugin = ?",array($plugin_name))->count();
if($check > 0){
	err($plugin_name.' has already been installed!');
}else{
 $fields = array(
	 'plugin'=>$plugin_name,
	 'status'=>'installed',
 );
 $db->insert('us_plugins',$fields);
 if(!$db->error()) {
	 	err($plugin_name.' installed');
		logger($user->data()->id,"USPlugins",$plugin_name." installed");
 } else {
	 	err($plugin_name.' was not installed');
		logger($user->data()->id,"USPlugins","Failed to to install plugin, Error: ".$db->errorString());
 }
}

$db->query("ALTER TABLE users ADD COLUMN plg_sub_level int(11) DEFAULT 0");
$db->query("ALTER TABLE users ADD COLUMN plg_sub_cost int(11) DEFAULT 0");
$db->query("ALTER TABLE users ADD COLUMN plg_sub_cred dec(11,2) DEFAULT 0");
$db->query("ALTER TABLE users ADD COLUMN plg_sub_exp date");
$db->query("ALTER TABLE users ADD COLUMN plg_sub_expired tinyint(1) default 1");

$db->query("CREATE TABLE `plg_sub_plans` (
  `id` int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `plan_name` varchar(255),
  `plan_desc` text,
  `perms_added` varchar(255),
	`icon` varchar(255),
	`ordering` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

$db->query("CREATE TABLE `plg_sub_cost` (
  `id` int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `plan` varchar(255),
  `days` int(11),
  `cost` DEC(11,2),
  `stripe_reccuring` varchar(255),
  `stripe_price_id` varchar(255),
  `icon` varchar(255)
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

$db->query("CREATE TABLE `plg_sub_settings` (
  `id` int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `sym` varchar(1) DEFAULT '$' ,
  `cur` varchar(3) DEFAULT 'usd'
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

$db->query("CREATE TABLE `plg_sub_stripe` (
  `id` int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `stripe_s` varchar(255) ,
  `stripe_p` varchar(255) ,
  `stripe_w` varchar(255) ,
  `stripe_currency` varchar(3),
  `stripe_coupons` varchar(3) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

$db->query("CREATE TABLE `plg_sub_stripe_customers` (
  `id` int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user` varchar(255) NOT NULL,
  `stripe_customer` varchar(255) NOT NULL,
  `stripe_subscription` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1");



$usersc_dir = "{$abs_us_root}{$us_url_root}usersc/";
$plugin_dir = "{$abs_us_root}{$us_url_root}usersc/plugins/{$plugin_name}/files/";
$files = array_diff(scandir($plugin_dir), ['..', '.']);
foreach ($files as $file) {
  $file_source_path = "{$plugin_dir}{$file}";
  $file_dest_path = "{$usersc_dir}{$file}";
  if (copy($file_source_path, $file_dest_path)) {
      logger($user->data()->id, 'Subscriptions', "[INSTALL] [FILES] [SUCCESS] Copied {$file}");
  } else {
      logger($user->data()->id, 'Subscriptions', "[INSTALL] [FILES] [ERROR] Failed to Copy {$file}");
  }
}





$check = $db->query("SELECT * FROM plg_sub_settings")->count();
if($check < 1){
	$fields = array('sym'=>'$','cur'=>'usd');
	$db->insert('plg_sub_settings',$fields);
}

$check2 = $db->query("SELECT * FROM plg_sub_stripe")->count();
if($check < 1){
	$fields2 = array('stripe_s'=>'sk_xxx','stripe_p'=>'pk_xxx','stripe_w'=>'whsec_xxx','stripe_currency'=>'usd','stripe_coupons'=>'off');
	$db->insert('plg_sub_stripe',$fields2);
}
$db->query("ALTER TABLE plg_sub_settings ADD COLUMN payments tinyint(1) default 0");
$db->query("ALTER TABLE plg_sub_cost ADD COLUMN disabled tinyint(1) default 0");
$db->query("ALTER TABLE plg_sub_plans ADD COLUMN disabled tinyint(1) default 0");
$db->query("ALTER TABLE plg_sub_plans ADD COLUMN script_add varchar(255)");
$db->query("ALTER TABLE plg_sub_plans ADD COLUMN script_remove varchar(255)");
$db->query("ALTER TABLE plg_sub_cost ADD COLUMN descrip varchar(255)");

//do you want to inject your plugin in the middle of core UserSpice pages?
$hooks = [];
$hooks['account.php']['bottom'] = 'hooks/accountbottom.php';
$hooks['account.php']['body'] = 'hooks/accountbody.php';
registerHooks($hooks,$plugin_name);

} //do not perform actions outside of this statement
