<?php
//Please don't load functions system-wide if you don't need them system-wide.
// To make your plugin more efficient on resources, consider only loading resources that need to be loaded when they need to be loaded.
// For instance, you can do
// $currentPage = currentPage();
// if($currentPage == 'admin.php'){ //The administrative dashboard
//   bold("<br>See! I am only loading this when I need it!");
// }
// // Also, please wrap your functions in if(!function_exists())
// if(!function_exists('membershipFunction')) {
//   function membershipFunction(){ }
// } 
/*
function haltPayment($option){
  $db = DB::getInstance();
  $check = $db->query("SELECT * FROM plg_payments_options WHERE `option` = ? AND enabled = 1",[$option])->count();
  if($check < 1){
    return true;
  }else{
    return false;
  }
}

function showPaymentOptions($opts = []){
  $db = DB::getInstance();
  $q = $db->query("SELECT * FROM plg_payments_options WHERE enabled = 1");
  $c = $q->count();
  $r = $q->results();
    echo "<label>Please select a Payment Option</label>";
  if($c < 1){
    echo "All payment options are currently disabled. Please contact an administrator<br>";
  }else{
    ?>
    <div class="form-group">
      <select class="<?php if(isset($opts['class'])){echo $opts['class'];}?>" name="paymentOption" required>
        <?php if($c > 1){?>
        <option value="" disabled>--Please select a payment option</option>
      <?php }
      foreach($r as $p){ ?>
        <option value="<?=$p->option?>"><?=ucfirst($p->option)?></option>
      <?php } ?>
      </select>
    </div>
<?php
  }
}

function displayPayment($formInfo){
  global $user,$db,$abs_us_root,$us_url_root;
  $method = $formInfo['method'];
    require $abs_us_root.$us_url_root.'usersc/plugins/membership/form_checkout_required.php';
    if(isset($formInfo['submit']) && $formInfo['submit'] != ""){
      echo $formInfo['submit'];
    }else{
      echo "<button class='btn btn-primary payment-form' type='submit'>Submit Payment</button><br>";
    }
    require $abs_us_root.$us_url_root.'usersc/plugins/payments/assets/'.$method.'/form_bottom.php';
}


function payment2($formInfo){
  global $user,$db,$abs_us_root,$us_url_root;
  $method = $formInfo['method'];
  	require $abs_us_root.$us_url_root.'usersc/plugins/membership/form_checkout_required.php';
    return $formInfo;
}
*/

function echoPlanName($id){
  global $db;
  $q = $db->query("SELECT plan_name FROM plg_sub_plans WHERE id = ?",[$id]);
  $c = $q->count();
  if($c > 0){
    $f = $q->first();
    echo $f->plan_name;
  }else{
    echo "None";
  }
}

function subscriptionPlanStatus(){
  global $user;
  $date = date("Y-m-d H:i:s");
  if($user->data()->plg_sub_exp == ""){
    $status = "None";
  }elseif($user->data()->plg_sub_exp < $date){
    $status = "Expired";
  }else{
    $status = "Active";
  }
return $status;
}

function changeOfPlans($from,$to,$uid){
  global $user,$us_url_root,$abs_us_root;
  $db = DB::getInstance();
  if($from > 0){
    $q = $db->query("SELECT * FROM plg_sub_plans WHERE id = ?",[$from]);
    $c = $q->count();
    if($c > 0){
      $f = $q->first();
      $perms = explode(",",$f->perms_added);
      foreach($perms as $p){
        $db->query("DELETE FROM user_permission_matches WHERE user_id = ? AND permission_id = ?",[$uid,$p]);
      }
    }
    if($f->script_remove != '' && file_exists($abs_us_root.$us_url_root.'usersc/plugins/subscriptions/scripts/'.$f->script_remove)){
      include $abs_us_root.$us_url_root.'usersc/plugins/subscriptions/scripts/'.$f->script_remove;
    }
  }
  if($to > 0){
    $q = $db->query("SELECT * FROM plg_sub_plans WHERE id = ?",[$to]);
    $c = $q->count();
    if($c > 0){
      $f = $q->first();
      $db->update('users',$user->data()->id,['plg_sub_level'=>$to,'plg_sub_expired'=>0]);
      $perms = explode(",",$f->perms_added);
      foreach($perms as $p){
        $fields = array(
          'permission_id'=>$p,
          'user_id'=>$uid,
        );
        $check = $db->query("SELECT * FROM user_permission_matches WHERE permission_id = ? AND user_id = ?",[$p,$uid])->count();
        if($check < 1){
                  $db->insert('user_permission_matches',$fields);
        }
      }
    }
    if($f->script_add != '' && file_exists($abs_us_root.$us_url_root.'usersc/plugins/subscriptions/scripts/'.$f->script_add)){
      include($abs_us_root.$us_url_root.'usersc/plugins/subscriptions/scripts/'.$f->script_add);
    }
  }
}






