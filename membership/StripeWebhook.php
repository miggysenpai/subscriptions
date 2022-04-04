<?php
//required for UserSpice
require_once '../../../users/init.php';
global $user,$settings;


echo "hallo sir";

$keys = $db->query("SELECT * FROM `plg_mem_stripe`")->first(); 



//it is important to go to https://dashboard.stripe.com/test/webhooks and add an end point with the URL of this page and add  "invoice.payment_succeeded" in event types
//ex. URL "https://yourdomain.com/usersc/plugins/membership/StripeWebhook.php?webhook=webhook"


$webhook = Input::get('webhook'); 

if(!empty($webhook == "webhook")){
 
require 'vendor/autoload.php';

// This is your test secret API key.
\Stripe\Stripe::setApiKey($keys->stripe_s);
// Replace this endpoint secret with your endpoint's unique secret
// If you are testing with the CLI, find the secret by running 'stripe listen'
// If you are using an endpoint defined with the API or dashboard, look in your webhook settings
// at https://dashboard.stripe.com/webhooks
$endpoint_secret = $keys->stripe_w;

$payload = @file_get_contents('php://input');
$event = null;

try {
  $event = \Stripe\Event::constructFrom(
    json_decode($payload, true)
  );
} catch(\UnexpectedValueException $e) {
  // Invalid payload
  echo '⚠️  Webhook error while parsing basic request.';
  http_response_code(400);
  exit();
}
if ($endpoint_secret) {
  // Only verify the event if there is an endpoint secret defined
  // Otherwise use the basic decoded event
  $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
  try {
    $event = \Stripe\Webhook::constructEvent(
      $payload, $sig_header, $endpoint_secret
    );
  } catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    echo '⚠️  Webhook error while validating signature.';
    http_response_code(400);
    exit();
  }
  
  
// Just getting all the values needed from stripe array
// echo $event and check the webhook return in stripe for the full array ;P
$purchase_meta = $event->data->object->lines->data[0]->metadata->purchase_meta;  //User ID
$plan = $event->data->object->lines->data[0]->metadata->plan;
$cost = $event->data->object->lines->data[0]->metadata->cost; 
$status = $event->data->object->lines->data[0]->metadata->status;  
$expire = $event->data->object->lines->data[0]->metadata->exp;
$amount_paid = $event->data->object->amount_paid;
$change = $event->data->object->lines->data[0]->metadata->change;
$via = $event->data->object->lines->data[0]->metadata->via;
$customerid = $event->data->object->customer;
$subscriptionid = $event->data->object->lines->data[0]->subscription;

}


if ($event->type == 'invoice.payment_succeeded') {
          // Fulfill any orders, e-mail receipts, etc
          // To cancel the payment you will need to issue a Refund (https://stripe.com/docs/api/refunds)
            error_log('💰 Payment received!');
          
            $memset = $db->query("SELECT * FROM plg_mem_settings")->first();
            
            $memSettings = $db->query("SELECT * FROM plg_mem_settings")->first();
            $check2Q = $db->query("SELECT * FROM plg_mem_cost WHERE id = ? AND plan = ? AND disabled = 0",[$cost,$plan]); //should be fine to leave as is
            $check2C = $check2Q->count(); //should be fine to leave as is
            $check2 = $check2Q->first(); //should be fine to leave as is
            
              if($status == "None"){ 
                $newdate = new DateTime(date("Y-m-d")); 
                $newdate->add(new DateInterval('P'.$check2->days.'D')); 
                $db->update('users',$purchase_meta,['plg_mem_exp'=>$newdate->format('Y-m-d'),'plg_mem_level'=>$plan]); 
                $db->query("INSERT INTO plg_mem_stripe_custID SET user ='$purchase_meta', stripe_customer = '$customerid', stripe_subscription = '$subscriptionid'");
                changeOfPlans($change,$plan,$purchase_meta); 
                logger($purchase_meta,"Membership","Paid ".$amount_paid." via ".$via." for level $plan at cost $cost."); // logger
                  
              } else {
                $newdate = new DateTime($expire); 
                $newdate->add(new DateInterval('P'.$check2->days.'D')); 
                $db->update('users',$purchase_meta,['plg_mem_exp'=>$newdate->format('Y-m-d')]); 
                $db->query("UPDATE plg_mem_stripe_custID SET stripe_customer = ?, stripe_subscription = ? WHERE user = ?",[$customerid,$subscriptionid, $purchase_meta]);
                logger($purchase_meta,"Membership","Extended Membership through ".$newdate->format('Y-m-d')." for ".$amount_paid); 
              }

        }
        else if ($event->type == 'payment_intent.payment_failed') {
          error_log('Payment failed.');
        }
        
        
        echo json_encode(['status' => 'success']);
      
        
        http_response_code(200);
    
}
?>