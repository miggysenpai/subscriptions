<?php
//Please don't load code on the footer of every page if you don't need it on the footer of every page.
//bold("<br>Subscription Footer Loaded");
if(isset($user) && $user->isLoggedIn() && $user->data()->plg_sub_expired == 0){
  if($user->data()->plg_sub_exp < date("Y-m-d H:i:s")){
    $fields = array(
      'plg_sub_expired'=>1,
    );
    $db->update('users',$user->data()->id,$fields);
    $test = changeOfPlans($user->data()->plg_sub_level,0,$user->data()->id);

global $currentPage;
  if($currentPage != 'login.php' && $currentPage != 'logout.php' &&  $currentPage != 'maintenance.php' &&  $currentPage != 'account.php' && !hasPerm([2],$user->data()->id)){
    Redirect::to($us_url_root.'users/account.php?err=Your+plan+has+expired');
  }
  }
}
