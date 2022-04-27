<?php
//required for UserSpice
ini_set('max_execution_time', 1356);
ini_set('memory_limit','1024M');

require_once '../../../users/init.php';
global $user,$settings;
$keys = $db->query("SELECT * FROM `plg_sub_stripe`")->first(); 
// ****** Stripe composer 
require_once($abs_us_root.$us_url_root.'usersc/plugins/subscriptions/vendor/autoload.php');
$stripe = new \Stripe\StripeClient($keys->stripe_s);
$data = json_decode(file_get_contents('php://input'), true);
// **** Get acutal price. aint nobody gunna inspect element scam you!!
$price_id = $data['price_id'];
$price_stripe = $stripe->prices->retrieve($price_id,[]);
$unit_amount = $price_stripe->unit_amount;

// **** Retrieve coupon
$coupon = strtoupper($data['coupon']);
$couponsRetrieve =$stripe->coupons->all([]);
    
//compares current coupon to every coupon
foreach($couponsRetrieve as $C){ 
    // if coupon mathches to then do this
    if($C->id == $coupon ){
        $percent_off = $C->percent_off;
        $total_amount = $unit_amount - ($unit_amount * ($percent_off / 100));
        $couponValid = ["is_valid"=> true , "id" => $C->id, "percent_off" => $C->percent_off ,"new_total" => $total_amount];
    }
}

if($couponValid == true){
    echo json_encode($couponValid);
} else {
    // coupon not valid sir
    $couponNoValid = array("is_valid"=> false);
    echo json_encode($couponNoValid);
}





?>