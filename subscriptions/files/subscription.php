<?php
ini_set('max_execution_time', 1356);
ini_set('memory_limit','1024M');

require_once '../users/init.php';
if (!securePage($_SERVER['PHP_SELF'])) {
    die();
}
require_once $abs_us_root.$us_url_root.'users/includes/template/prep.php';
$hooks = getMyHooks();
includeHook($hooks, 'pre'); 

$get_info_id = $user->data()->id;
$raw = date_parse($user->data()->join_date);
$signupdate = $raw['month'].'/'.$raw['day'].'/'.$raw['year'];
$userdetails = fetchUserDetails(null, null, $get_info_id); // Fetch user details
$keys = $db->query("SELECT * FROM `plg_sub_stripe`")->first();  // Fetch required stripe keys
$stripeCustSub = $db->query("SELECT * FROM `plg_sub_stripe_customers` WHERE user = ?",[$user->data()->id])->first(); 
$stripe_customer = $stripeCustSub->stripe_customer;
$stripe_sub = $stripeCustSub->stripe_subscription;
?>

<div class="container">
<?php 
if(pluginActive('subscriptions',true)){
$status = ""; 
global $user,$settings;
$subset = $db->query("SELECT * FROM plg_sub_settings")->first();
$status = subscriptionPlanStatus();
$extend = Input::get('extend');
$subSettings = $db->query("SELECT * FROM plg_sub_settings")->first();
$plans = $db->query("SELECT * FROM plg_sub_plans ORDER BY ordering")->results();
$ps = false;
$pl = Input::get('ps');
if(is_numeric($pl)){$ps = true;}   
$sel = false;  
$subscriptionChange = Input::get('change');
$userdetails=$user->data(); 

$opt = Input::get('opt'); 
$pass = false;
$plan = Input::get('plan');
$cost = Input::get('plan_cst');
$subChange = Input::get('subscription');
$getPayment = Input::get('payment');
$display = Input::get('display');
// ****** Stripe composer 
require_once($abs_us_root.$us_url_root.'usersc/plugins/subscriptions/vendor/autoload.php');
$stripe = new \Stripe\StripeClient($keys->stripe_s);

                    
// ****** SUBSCRIPTION MADE, PROCCESSING CODE
	if(!empty($_POST && $subscriptionChange == "subscription" && $opt == "process" )){
            // Setting important variables
            $name = $_POST['cname'];
            $email = $userdetails->email; // User email
            $token = $_POST['stripeToken']; // Payment source
            $stripe_price = $_POST['stripe_price']; // Price id
            $stripe_coupon = $_POST['stripe_coupon']; // Coupon available
                    
            // Create customer
            $customer = $stripe->customers->create([
            'name' => $name,
            'email' => $email,
            'source' => $token  
            ]);
                    
            $customerid = $customer->id;
                    
            // Basic array required to make subscription
            $subscriptionsArrayBase = [
            'customer' => $customerid,
            'items' =>[
                        ['price' => $stripe_price]
                    ],
            ];
                    
            // This metadata is important so the webhook works    
            $subscriptionsArrayMeta = [
            'metadata' => [
                'purchase_meta' => $user->data()->id,
                'plan' => $plan,
                'status' => $status, 
                'cost' => $cost,
                'exp' => $user->data()->plg_sub_exp,
                'change' =>$user->data()->plg_sub_level,
                'via' => 'stripe',
                ]
            ];
                    
            // Check if coupon setting is enabled
            if($keys->stripe_coupons == true){
                $subscriptionsArrayCoupon = [
                    'coupon' => $stripe_coupon,
                ];
            } 
            else {
                $subscriptionsArrayCoupon= [];
            }
                    
            $subscriptionsArrays = $subscriptionsArrayBase + $subscriptionsArrayMeta + $subscriptionsArrayCoupon;
                    
            //create subscription, (Request POST body to stripe)
            $subscription = $stripe->subscriptions->create([$subscriptionsArrays,]);
                    
            if($subscription->status == 'active'){
                //woot woot, it works 
                header("Location: subscription.php?&display=success&stripemsg=subcomplete");
            }else {
                echo ' problema sir';
            }
	}// Closing tag
	

// ******* CHECKOUT CODE
	if(!empty($_POST && $subscriptionChange == "subscription" && $opt == "checkout" )){
		   $po = Input::get('paymentOption');
		   if($status == "Active" && $plan != $user->data()->plg_sub_level){
		         Redirect::to('account.php?change=subscription&err=Only+an+admin+can+change+your+plan');
		   } 
		   $cost = Input::get('plan_cst');
		   $check1 = $db->query("SELECT * FROM plg_sub_plans WHERE disabled = 0 AND id = ?",[$plan])->count();
	           $check2Q = $db->query("SELECT * FROM plg_sub_cost WHERE id = ? AND plan = ? AND disabled = 0",[$cost,$plan]);
		   $check2C = $check2Q->count();
		
		   if($check1 < 1 || $check2C < 1){
			Redirect::to('account.php?change=subscription&err=Invalid+plan+selected');
		   }
		
		   $check2 = $check2Q->first();
		   $check3Q = $db->query("SELECT * FROM plg_sub_plans WHERE disabled = 0 AND id = ?",[$plan]); 
		   $check3 = $check3Q->first();
		   $pass = true;
	}// Closing tag
	
	
// ****** SUBSCRIPTION CANCELLED ;c
     	if($subChange == "unsubscribe"){
     	    $subscription = $stripe->subscriptions->retrieve($stripe_sub,[]);
     	        // Checks if sub is active, then cancel it
         	    if($subscription->status == "active"){
         	        $stripe->subscriptions->cancel($stripe_sub,[]);
         	    header("Location: subscription.php?&display=success&stripemsg=subcanceled");
            	}else{
            	    header("Location: subscription.php");
            	}
     	}// Closing tag
     	
     	
// ******  SUBSCRIPTION UPDATED	
     	if($subChange == "updateProcess" && $_POST){
     	    $plan = Input::get('plan');
     	    $cost = Input::get('plan_cst');
     	    $costs = $db->query("SELECT * FROM plg_sub_cost WHERE id = ?",[$cost])->first();
     	    $subscription = $stripe->subscriptions->retrieve($stripe_sub);
     	    
         	    if($subscription->status == "active"){
         	  // cancel current subscription      
         	    $cancel_sub = $stripe->subscriptions->cancel($stripe_sub);
         	  //create new subscription
         	    $subscription = $stripe->subscriptions->create([
                    'customer' => $stripe_customer,
                    'items' =>[
                        ['price' => $costs->stripe_price_id, ]
                      ],
                    // This meta data is important so the webhook works
                    'metadata' => [
                            'purchase_meta' => $user->data()->id,
                            'plan' => $plan,
                            'status' => $status, 
                            'cost' => $cost,
                            'exp' => $user->data()->plg_sub_exp,
                            'change' =>$user->data()->plg_sub_level,
                            'via' => 'stripe',
                          ]
                    ]);
         	     header("Location: subscription.php?&display=success&stripemsg=subupdated");
         	    }
         	    if($subscription->status == "canceled"){
         	  //create new subscription
         	    $subscription = $stripe->subscriptions->create([
                    'customer' => $stripe_customer,
                    'items' =>[
                        ['price' => $costs->stripe_price_id, ]
                      ],
                      //this meta data is important so the webhook works
                    'metadata' => [
                            'purchase_meta' => $user->data()->id,
                            'plan' => $plan,
                            'status' => $status, 
                            'cost' => $cost,
                            'exp' => $user->data()->plg_sub_exp,
                            'change' =>$user->data()->plg_sub_level,
                            'via' => 'stripe',
                          ]
                    ]);
                    header("Location: subscription.php?&display=success&stripemsg=subupdated");
         	    }
     	} // Closing tag
     	
     	
// ******  ADD A PAYMENT     	
     	if($getPayment == "add" && $_POST){
            $token = $_POST['stripeToken'];
            $stripe->customers->createSource($stripe_customer,['source' => $token]);
            header("location: subscription.php?&display=success&stripemsg=paymentadded"); 
     	} // Closing tag
     	
     	
// ******  SET DEFAULT PAYMENT	
        if($getPayment == "setDefault"){
     	    $stripe->customers->update($stripe_customer,['default_source' => (Input::get('id')) ]);
            header("location: subscription.php?&display=success&stripemsg=paymentdefault"); 
     	} // Closing tag
     	
// ******  DELETE CARD	
        if($getPayment == "delete"){
     	    $stripe->customers->deleteSource($stripe_customer,(Input::get('id')),[]);
            header("location: subscription.php?&display=success&stripemsg=paymentdeleted");
     	}
    
?>

<link rel="stylesheet" href="<?=$us_url_root?>usersc/plugins/subscriptions/subscriptions.css">
<script src="https://js.stripe.com/v3/"></script> 


    
 <?php  
 // ***** DISPLAY SUCCESS PAGES
if($display == "success"){
$msgOutput = Input::get('stripemsg');
?>
    <br /> <br />
    <div class="card mb-4 py-3 border-bottom-success text-center">
        <div class="card-body">
            <? 
            if($msgOutput =="subcomplete"){
                echo "Payment was successful! Enjoy".$settings->site_name."!<br /><br /> The receipt was send to".$userdetails->email;
            }
            if($msgOutput =="subcanceled"){
                echo "Subscription was canceled successfully! <br /><br /> Sad to see you go :c";
            }
            if($msgOutput =="subupdated"){
                echo "Subscription was updated successfully! <br /><br /> The receipt was send to".$userdetails->email;
            }
            if($msgOutput =="paymentadded"){
                echo "Payment was added successfully!<br /><br /> You can use that payment for future subscriptions!";
            }       	    
            if($msgOutput =="paymentdefault"){
                echo  "Default payment was updated successfully!<br /><br /> This payment will be used for future subscriptions!";
            }
            if($msgOutput =="paymentdeleted"){
                echo "Payment was deleted successfully<br /><br /> You can alway add the payment method back if it was a mistake!";
            } 
            ?>
            <br /><br />
            <p class="text-center small">Redirecting to Subcriptions page...
                <br />
                <div class="d-flex justify-content-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Redirecting...</span>
                    </div>
                </div>
            </p>
            
        </div>
    </div></div></div>
    </div>
    <script>
         setTimeout(function(){
            window.location.href = '?';
         }, 5000);
      </script>
    
<?php } // Closing tag

 
// ****** SUBSCRIPTION CHOICES
if($subscriptionChange == "subscription" && !$pass && $getCoupon == ""){ ?>
  <form class="" action="subscription.php?change=subscription&opt=checkout" method="post">
    <div class="form-group">
        <div class="card border-0 shadow-lg pt-5 my-5 position-relative">
            <div class="card-body">
              
              <div class="card-text pt-1">
                <div class="mb-3 text-center" >
                  
                    <section class="background">
                        <div class="paper">
                            <header class="header-title">Subscription <span></span></header>
                              <aside class="radio-container">
                                <?php foreach($plans as $p){
                                // select the plan
                                ?>
                                    <input type="radio" name="plan" class="planOption" id="<?=$p->plan_name?>"  value="<?=$p->id?>"  checked required/>
                                    <label for="<?=$p->id?>" class="lbl-radio">
                                      <div class="content">
                                        <div class="title"><?=$p->plan_name?></div>
                                      </div>
                                    </label>
                                <?php  } ?>
                    </aside>
                        </div>
                    </section>
                </div>  
                    <div class="form-group text-center">
                        <?// select the pricing ?>
                        <section class="background">
                            <div class="paper">
                              <header class="header-title">Choose service <span>Subscription</span></header>
                              <aside class="radio-container">
                                    <?php
                                    $costs = $db->query("SELECT * FROM plg_sub_cost WHERE disabled = '0' ORDER BY days ")->results();
                                    foreach($costs as $p){ ?>
                                    <!-- radio -->
                                        <input type="radio" name="plan_cst" id="<?=$p->id?>" data-plan="<?=$p->plan?>" value="<?=$p->id?>" required />
                                        <label for="<?=$p->id?>" class="lbl-radio">
                                          <div class="marker"></div>
                                          <div class="content">
                                            <div class="title"><?=$p->descrip?></div>
                                            <div class="subtext"><?=$subset->sym?><?=$p->cost?> every <?=$p->days?> Days</div>
                                          </div>
                                        </label>
                                    <?php   } ?>
                                    
                                  <!--  </select>-->
                                    </div>
                                </aside>
                            </div>
                        </section>
                                <input name="paymentOption" required  style="display: none;" id="paymentOption"  value="stripe"/>
              </div>
            </div><!--card-body-->
            <div class="card-footer theme-bg-primary border-0 text-center">
              <ul class="social-list list-inline mb-0 mx-auto">
                    <li class="list-inline-item">
                    <input type="submit" name="newPlan" value="Select Plan" id="subBtn" class="btn  btn-main-main"></li>
                  </ul><!--social-list-->
            </div><!--card-footer-->
          </div><!--card-->
    </div>
  </form>
<?php }



// ****** SUBSCRIPTION CHECKOUT
if($subscriptionChange == "subscription" && $pass && $opt == "checkout"){
$stripe_price = $db->query("SELECT * FROM plg_sub_cost WHERE id = ?",[$cost])->first();
?>
<br />
<br /><br />
<div class="container text-center">
    <div class="row">
        <div class="col-md-12">
            <div class="row  align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-0"><?=$check3->plan_name?></div>
                    <div class="font-weight-bold text-uppercase mb-0 titleCost">$ <span id="cost_total" class=" mb-0 titleCost"><?=$check2->cost?></span></div>
                    <div class="text-xs  font-weight-bold text-secondary text-uppercase mb-0"><?=$check2->descrip?> </div><br /><br />
                </div> 
            </div>
        </div>   
        <div class="col-md-12 ">
            <div id="checkout">
                <form  action="subscription.php?change=subscription&opt=process" method="post" id="payment-form">
                <div id="payment-request-button">
                   <!-- A Stripe Element will be inserted here if the browser supports this type of payment method. -->
                </div><br />
                
                <div class="hr"><hr class="hr"/></div>
                <section>
                    <fieldset class="with-state">
                        <label>
                            <span>Email</span>
                            <input name="email" type="email" class="field" value="<?=$user->data()->email?>" readonly required>
                        </label>
                        <label>
                            <span>Name</span>
                            <input name="cname" class="field" placeholder="Jenny Rosen" required>
                        </label>
                        <div class="payment-info">
                            <input name="stripe_price" style="display: none;" class="planOption"  id="stripe_price"  value="<?=$stripe_price->stripe_price_id?>"> 
                            <input type="hidden" name="plan_cst" value="<?=$cost?>">
                            <input type="hidden" name="plan" value="<?=$plan?>">
                            <input type="hidden" name="paymentOption" value="<?=$po?>">   
                            <? //stripe code below required for checkout /?>
                            <label>
                                <span>Card</span>
                                <div id="card-element" class="field"></div>
                                <div id="card-errors" role="alert"></div>
                            </label>
                            <?
                            if($keys->stripe_coupons == true){ echo
                                "<label>
                                <span>Coupon</span>
                                <input id='coupon-input' type='text' placeholder='Coupon'   >
                                <input id='stripe_coupon' name='stripe_coupon' type='hidden' class='field' name='stripe_coupon'>
                                </label>";
                            } 
                            ?>
                        </div>
                    </fieldset>
                    <?
                    if($keys->stripe_coupons == true){ echo
                        "<button id='coupon-button' class='col-sm-12' >Apply Coupon</button>
                         <center><h2 id='error-label' /></center>
                        ";
                    }?>
                    <button type="submit" class="col-sm-12" >Pay $ <span id="cost_total2"><?=$check2->cost?></span></button>
                    <div id="messages" role="alert"></div>
                </section>
              </form> <br /><br />
            </div>
        </div>
    </div>
</div>
    <br />
    
    <script>
    
    // Helper for displaying status messages.
    const addMessage = (message) => {
      const messagesDiv = document.querySelector('#messages');
      messagesDiv.style.display = 'block';
      const messageWithLinks = addDashboardLinks(message);
      messagesDiv.innerHTML += `> ${messageWithLinks}<br>`;
      console.log(`Debug: ${message}`);
    };
    
    // Adds links for known Stripe objects to the Stripe dashboard.
    const addDashboardLinks = (message) => {
      const piDashboardBase = 'https://dashboard.stripe.com/test/payments';
      return message.replace(
        /(pi_(\S*)\b)/g,
        `<a href="${piDashboardBase}/$1" target="_blank">$1</a>`
      );
    };
    
    var paymentCost = <?=$check2->cost*100?>;
    
    // Set your publishable key: remember to change this to your live publishable key in production
    // See your keys here: https://dashboard.stripe.com/apikeys
    var stripe = Stripe('<?=$keys->stripe_p?>');
    var elements = stripe.elements();

    // Custom styling can be passed to options when creating an Element.
    var style = {
      base: {
        // Add your base input styles here. For example:
        fontSize: '16px',
        color: '#32325d',
      },
    };

    // Create an instance of the card Element.
    var card = elements.create('card', {style: style});

    // Add an instance of the card Element into the `card-element` <div>.
    card.mount('#card-element');

    // Create a token or display an error when the form is submitted.
    var form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
      event.preventDefault();

      stripe.createToken(card).then(function(result) {
        if (result.error) {
          // Inform the customer that there was an error.
          var errorElement = document.getElementById('card-errors');
          errorElement.textContent = result.error.message;
        } else {
          // Send the token to your server.
          stripeTokenHandler(result.token);
        }
      });
    });

    function stripeTokenHandler(token) {
      // Insert the token ID into the form so it gets submitted to the server
      var form = document.getElementById('payment-form');
      var hiddenInput = document.createElement('input');
      hiddenInput.setAttribute('type', 'hidden');
      hiddenInput.setAttribute('name', 'stripeToken');
      hiddenInput.setAttribute('value', token.id);
      form.appendChild(hiddenInput);

      // Submit the form
      form.submit();
    }
    
    var couponInput = document.getElementById('coupon-input');
    var couponButton = document.getElementById('coupon-button');
    var errorLabel = document.getElementById('error-label');
    var costTotal = document.getElementById('cost_total');
    var costTotal2 = document.getElementById('cost_total2');
    
    
    couponButton.addEventListener('click', function(e) {
        e.preventDefault();

        fetch('<?=$us_url_root?>usersc/plugins/subscriptions/couponCheck.php', {
          method: 'POST',
          
          body: JSON.stringify({
            coupon: couponInput.value,
            price_id : '<?=$stripe_price->stripe_price_id?>',
          })
        })
        .then(response => {  return response.json();})
        .then((data) => {
          if(data.is_valid) {
            // Coupon applied  
            displayLabelCoupon("Coupon applied!");
            // Update coupon for checkout
            document.getElementById("stripe_coupon").value = (data.id);
            var costPre = "<?=$check2->cost?>";
            var discount = (data.percent_off);
            var afterDiscount = (costPre - (costPre * (discount/100))).toFixed(2);
            displayLabelCost(afterDiscount);
            // addMessage(`Total ${data.new_total} `);
          }
          
          else {
            displayLabelCoupon("Invalid coupon, try again");
          }
        })
      })
    
    function displayLabelCost(text) {
        console.log(text);
        costTotal.innerHTML = text;
        costTotal2.innerHTML = text;
    }
    
    function displayLabelCoupon(text) {
        console.log(text);
        errorLabel.innerHTML = text;
    }
  </script>
<?    } }//end tag  ?>


<?php 

// ****** Customer Portal
        if($subscriptionChange == "" && $opt == "" &&  $subChange == "" && $getPayment == "" && $getCoupon == "" && $display == ""){
        ?>
        <h6>Current Plan | <span ><?=$status?></span></h6>
        <hr />
            <?php 
            if($status == "Active"){
                    $customer = $stripe->customers->retrieve($stripe_customer,[]);
                    // Remove to view JSON
                    // echo "<pre>".$customer."</pre>";
                    $stripe_price = $customer->subscriptions->data[0]->items->data[0]->price->id;
                    $subscription_status = $customer->subscriptions->data[0]->status;
                    $price_unit_amount = $customer->subscriptions->data[0]->items->data[0]->price->unit_amount;
                    $price_unit_amount_d = number_format($price_unit_amount/pow(10,2), 2);
                    $price_currency = $customer->subscriptions->data[0]->items->data[0]->price->currency;
                    
                    // Checks for the interval; billing stuff
                    $price_interval = $customer->subscriptions->data[0]->items->data[0]->price->recurring->interval;
                    $price_interval_count = $customer->subscriptions->data[0]->items->data[0]->price->recurring->interval_count;
                    if($price_interval_count < 1.1){ } else { 
                        $price_interval_c = $price_interval_count;
                        $s = "s";
                    }
                    
                    $invoiceLoop =  $stripe->invoices->all(['customer' => $stripe_customer]);
                    $cardLoop = $stripe->paymentMethods->all(['customer' => $stripe_customer, 'type' => 'card', ]);
                    
                    $default_payment = $stripe->customers->retrieve($stripe_customer,[]);
            ?>
            
            <div class="row"> 
                <div class="col-md-6">
                    <h3><strong><?=echoPlanName($user->data()->plg_sub_level);?></strong> </h3><br>
                    <h6>Autorenewal : <span class="text-capitalize"><?=$subscription_status?></span></h6>
                    <h6><?=$subset->sym?> <?=$price_unit_amount_d?>
                        <span class="text-uppercase"><?=$price_currency?> /</span>
                        <span class="text-capitalize"><?=$price_interval_c?> <?=$price_interval?><?=$s?></span>
                    </h6>
                    <h6>Subscriptions Good Until : <?=$user->data()->plg_sub_exp;?> </h6>
                </div>
                <div class="col-md-6">  
                    <a href="?subscription=update"><button type="button" class="btn btn-primary btn-lg">Update Plan</button></a>
                        <br />
                    <a href="?subscription=unsubscribeConfirm"><button type="button" class="btn btn-light btn-lg">Cancel Plan</button></a>
                </div>
            </div>
            <br/><br /><br />
            <div class="row">  
            <div class="col-md-12">
            <h6>Payment Methods</h6>
            <hr />
            </div>
            <br /><br /><br />
            <div class='col-md-6'>
            <table class="table table-borderless">
                <tbody>
                 <?
                 // Remove to view JSON LIST ARRAY 
                 // print("<pre>".print_r($cardLoop,true)."</pre>");
                foreach ($cardLoop as $card) {
                        echo "
                         <tr>
                          <td class='text-uppercase'>".$card->card->brand." •••• ".$card->card->last4."</td>
                          <td>".$card->card->exp_month."/".$card->card->exp_year."</td>
                          <td>"; if($default_payment->default_source == $card->id){ echo "<span class='badge badge-secondary'>Default</span>";} else 
                                    { echo "<a href='?payment=setDefault&id=".$card->id."''><span class='badge badge-light'>Set Default</span></a>";}
                        echo "</td>
                          <td><a href='?payment=delete&id=".$card->id."'> <span class='glyphicon glyphicon-remove-circle'></span></a></td>
                        </tr>
                        ";
                    }
                 ?>
                </tbody>
            </table>
            </div>
            <div class='col-md-6'>
                <a href="?payment=add"><button type="button" class="btn btn-primary btn-lg">Add Payment </button></a> <br />
            </div>
            </div>
            <br/><br /><br />
            <div class="row">  
                <h6>Invoices</h6>
                <table class='table '>
                 <thead> 
                    <? 
                    // Remove to view JSON LIST ARRAY 
                    // print("<pre>".print_r($invoiceLoop,true)."</pre>");
                    foreach ($invoiceLoop as $rslt) {
                        $epoch = $rslt->lines->data[0]->period->start;
                        $dt = new DateTime("@$epoch");  // convert UNIX timestamp to PHP DateTime
                        $amount_paid = $rslt->amount_paid/100;
                        echo "
                         <tr>
                          <td>".$rslt->lines->data[0]->description."</td>
                          <td>".$dt->format('m-d-Y')."</td>
                          <td class='text-capitalize'>".$amount_paid." <span class='text-uppercase'>".$rslt->currency." ".$rslt->status."</span</td>
                          <td class='text-capitalize'>".$rslt->discount->coupon->id."</td>
                          <td><a href='".$rslt->hosted_invoice_url."'>View <span class='glyphicon glyphicon-new-window'></span></a></td>
                        </tr>
                        "; }
                    ?>
                        </thead>
                </table>
                    
            </div>
            <?} else { 
                //take them to create a subscription and a customer id with stripe.
                header("Location: subscription.php?change=subscription"); }
        }


// ****** CANCEL SUBSCRIPTION
        if($subscriptionChange == "" && $opt == "" &&  $subChange == "unsubscribeConfirm"  && $getCoupon == ""){
        $plan = $user->data()->plg_sub_level;
     	$cost = $user->data()->plg_sub_cost;
     	$costs = $db->query("SELECT * FROM plg_sub_cost WHERE id = ?",[$cost])->first();
     	$plans = $db->query("SELECT * FROM plg_sub_plans WHERE id = ?",[$plan])->first();
     	if($costs->stripe_reccuring == "Every 1 month"){
     	    $recurring = "Monthly";
     	}
     	if($costs->stripe_reccuring == "Every 3 month"){
     	    $recurring = "Every 3 Months";
     	}
     	if($costs->stripe_reccuring == "Every 6 month"){
     	    $recurring = "Every 6 Months";
     	}
     	if($costs->stripe_reccuring == "Every 1 year"){
     	    $recurring = "Annually";
     	}
     	
     	$default_payment = $stripe->customers->retrieve($stripe_customer,[]);
     	$default_card = $stripe->customers->retrieveSource($stripe_customer, $default_payment->default_source,[]);
     	//echo "<pre>".$default_card."<pre>";
        ?>
        <div class="container">
            <div class="row"><div class="col"></div>
                <div class="col-md-8 col-sm-12">
        <form class="" action="subscription.php?subscription=updateProcess" method="post">
            <div class="card-body">
              <div class="card-text pt-1">
                <div class="card-text pt-1">
                    <div class="mb-3 text-center" >
                        <section class="background">
                            <div class="paper">
                                <header class="header-title">Cancel Subscriptions</header>
                                  <aside class="radio-container">
                                            
                                            <label for="" class="lbl-radio">
                                              <div class="content">
                                                <div class="title text-left"><?=$plans->plan_name?></div>
                                                <p class="text-left">Tier : <?=$costs->descrip?></p>
                                                <p class="text-left">Reccuring : <?=$recurring?></p>
                                                <p class="text-left">Price : $<?=$costs->cost?></p>
                                              </div>
                                            </label>
                                    </aside>
                                    <aside class="radio-container">
                                            
                                            <label for="" class="lbl-radio">
                                              <div class="content">
                                                <div class="title text-left">Cancel</div>
                                                <p class="text-left">Are you sure you want to cancel? If so, click the button below :c</p>
                                              </div>
                                            </label>
                                    </aside>
                                    <br />
                                    <a href="subscription.php?subscription=unsubscribe" class="btn  btn-primary btn-block">Cancel Plan</a>
                            </div>
                        </section>
                    </div>  
                    
                   </div>
                </div>
            </div><!--card-body-->
            </div><div class="col"></div>
            </div>
        </div>
          
        
        <?}

// ****** UPDATE SUBSCRIPTION
        if($subscriptionChange == "" && $opt == "" &&  $subChange == "update"  && $getCoupon == ""){?>
        <div class="container">
            <div class="row">
                <div class="col"></div>
                <div class="col-md-8 col-sm-12">
        <form class="" action="subscription.php?subscription=updateConfirm" method="post">
            <div class="card-body">
              <div class="card-text pt-1">
                   <div class="card-text pt-1">
                <div class="mb-3 text-center" >
                    <section class="background">
                        <div class="paper">
                            <header class="header-title">Subscriptions <span></span></header>
                              <aside class="radio-container">
                                    <?php foreach($plans as $p){
                                    // select the plan
                                    ?>
                                        <input type="radio" name="plan" class="planOption" id="<?=$p->plan_name?>"  value="<?=$p->id?>"  checked required/>
                                        <label for="<?=$p->id?>" class="lbl-radio">
                                          <div class="content">
                                            <div class="title"><?=$p->plan_name?></div>
                                          </div>
                                        </label>
                                    <?php  } ?>
                                </aside>
                        </div>
                    </section>
                </div>  
                
                <div class="form-group text-center">
                    <?// select the pricing ?>
                        <section class="background">
                            <div class="paper">
                              <header class="header-title">Choose service <span>Subscription</span></header>
                              <aside class="radio-container">
                                    <?php
                                    $costs = $db->query("SELECT * FROM plg_sub_cost WHERE disabled = '0' ORDER BY days ")->results();
                                    foreach($costs as $p){ 
                                    
                                    ?>
                                    <!-- Select -->
                                        <input type="radio" name="plan_cst" id="<?=$p->id?>" data-plan="<?=$p->plan?>" value="<?=$p->id?>" required />
                                        <label for="<?=$p->id?>" class="lbl-radio">
                                          <div class="marker"></div>
                                          <div class="content">
                                            <div class="title"><?=$p->descrip?></div>
                                            <div class="subtext"><?=$subset->sym?><?=$p->cost?> 
                                                <?
                                                    if($p->stripe_reccuring == "Every 1 month"){
                                                 	    echo "Monthly";
                                                 	}
                                                 	if($p->stripe_reccuring == "Every 3 month"){
                                                 	    echo "Every 3 Months";
                                                 	}
                                                 	if($p->stripe_reccuring == "Every 6 month"){
                                                 	    echo "Every 6 Months";
                                                 	}
                                                 	if($p->stripe_reccuring == "Every 1 year"){
                                                 	    echo  "Annually";
                                                 	}
                                                ?>
                                            </div>
                                          </div>
                                        </label>
                                    <?php   } ?>
                                    <input type="submit" value="Select Plan" id="subBtn" class="btn  btn-primary">
                                    </div>
                                </aside>
                            </div>
                        </section>
                        <input name="paymentOption" required  style="display: none;" id="paymentOption"  value="stripe"/>
             </div></div>
            </div><!--card-body-->
            </div>
            <div class="col"></div>
            </div>
            </div>
        <?
        }
        
        // ****** UPDATE PREVIEW SUBSCRIPTION
        if($subscriptionChange == "" && $opt == "" &&  $subChange == "updateConfirm"  && $getCoupon == ""){
        $plan = Input::get('plan');
     	$cost = Input::get('plan_cst');
     	$costs = $db->query("SELECT * FROM plg_sub_cost WHERE id = ?",[$cost])->first();
     	$plans = $db->query("SELECT * FROM plg_sub_plans WHERE id = ?",[$plan])->first();
     	if($costs->stripe_reccuring == "Every 1 month"){
     	    $recurring = "Monthly";
     	}
     	if($costs->stripe_reccuring == "Every 3 month"){
     	    $recurring = "Every 3 Months";
     	}
     	if($costs->stripe_reccuring == "Every 6 month"){
     	    $recurring = "Every 6 Months";
     	}
     	if($costs->stripe_reccuring == "Every 1 year"){
     	    $recurring = "Annually";
     	}
     	
     	$default_payment = $stripe->customers->retrieve($stripe_customer,[]);
     	$default_card = $stripe->customers->retrieveSource($stripe_customer, $default_payment->default_source,[]);
     	//echo "<pre>".$default_card."<pre>";
        ?>
        <div class="container">
            <div class="row"><div class="col"></div>
                <div class="col-md-8 col-sm-12">
        <form class="" action="subscription.php?subscription=updateProcess" method="post">
            <input name="plan" type="hidden" value="<?=$plan?>"/>
            <input name="plan_cst" type="hidden" value="<?=$cost?>"/>
            <div class="card-body">
              <div class="card-text pt-1">
                <div class="card-text pt-1">
                    <div class="mb-3 text-center" >
                        <section class="background">
                            <div class="paper">
                                <header class="header-title">Checkout</header>
                                  <aside class="radio-container">
                                            
                                            <label for="" class="lbl-radio">
                                              <div class="content">
                                                <div class="title text-left"><?=$plans->plan_name?></div>
                                                <p class="text-left">Tier : <?=$costs->descrip?></p>
                                                <p class="text-left">Reccuring : <?=$recurring?></p>
                                                <p class="text-left">Price : $<?=$costs->cost?></p>
                                              </div>
                                            </label>
                                    </aside>
                                    <aside class="radio-container">
                                            
                                            <label for="" class="lbl-radio">
                                              <div class="content">
                                                <div class="title text-left">Default Payment Method</div>
                                                <p class="text-left">Card : <?=$default_card->brand?> **** <?=$default_card->last4?></p> 
                                                <p class="text-left">Expires : <?=$default_card->exp_month?>/<?=$default_card->exp_year?></p>
                                              </div>
                                            </label>
                                    </aside>
                                    <aside class="radio-container">
                                            
                                            <label for="" class="lbl-radio">
                                              <div class="content">
                                                <div class="title text-left">Warning</div>
                                                <p class="text-left">Updating your subscription will reset the billing cycle and will charge your
                                                    default card the full amount. If you wish to use a different card for checkout, please update the new card to the default payment. </p> 
                                              </div>
                                            </label>
                                    </aside>
                                    <header class="header-title text-right"> <span>Due today : $<?=$costs->cost?></span></header>
                                    <br />
                                <input type="submit" value="Change Plan" id="subBtn" class="btn  btn-primary">
                            </div>
                        </section>
                    </div>  
                    
                   </div>
                </div>
            </div><!--card-body-->
            </div><div class="col"></div>
            </div>
        </div>
        <?
        }
        
        
        
// ****** ADD PAYMENT
        if($getPayment == "add" && $getCoupon == ""){ ?>
         
            <div class="row"><div class="col-md-2 col-sm-12"></div>
                <div class="col-md-8 col-sm-12">
        
            <input name="plan" type="hidden" value="<?=$plan?>"/>
            <input name="plan_cst" type="hidden" value="<?=$cost?>"/>
            <div class="card-body">
              <div class="card-text pt-1">
                <div class="card-text pt-1">
                    <div class="mb-3 text-center" >
                        <section class="background">
                            <div class="paper">
                                <header class="header-title">Add Payment</header>
                                  <aside class="radio-container">
                                            
                                            <label for="" class="lbl-radio2">
                                              <div class="content">
                                                <div class="title text-left">Add Card</div>
                                                <form  action="" method="post" id="payment-form">
                                                <section>
                                                  <fieldset class="with-state">
                                                     <div class="payment-info">
                                                        <label><span class="d-none d-sm-block">Card</span>
                                                            <div id="card-element" class="field"></div>
                                                            <div id="card-errors" role="alert"></div>
                                                        </label>
                                                    </div>
                                                  </fieldset>
                                                  <button type="submit" class="btn-block btn btn-primary" >Add Payment</button>
                                                </section>
                                             </form>
                                              </div>
                                            </label>
                                    </aside>
                                    <br />
                            </div>
                        </section>
                    </div>  
                    
                   </div>
                </div>
            </div><!--card-body-->
            </div><div class="col-md-2 col-sm-12"></div>
            </div>
        
        
            </div>
            <br />
                <script>
                // Set your publishable key: remember to change this to your live publishable key in production
                // See your keys here: https://dashboard.stripe.com/apikeys
                var stripe = Stripe('<?=$keys->stripe_p?>');
                var elements = stripe.elements();
            
                // Custom styling can be passed to options when creating an Element.
                var style = {
                  base: {
                    // Add your base input styles here. For example:
                    fontSize: '16px',
                    color: '#32325d',
                  },
                };
            
                // Create an instance of the card Element.
                var card = elements.create('card', {style: style});
            
                // Add an instance of the card Element into the `card-element` <div>.
                card.mount('#card-element');
            
                // Create a token or display an error when the form is submitted.
                var form = document.getElementById('payment-form');
                form.addEventListener('submit', function(event) {
                  event.preventDefault();
            
                  stripe.createToken(card).then(function(result) {
                    if (result.error) {
                      // Inform the customer that there was an error.
                      var errorElement = document.getElementById('card-errors');
                      errorElement.textContent = result.error.message;
                    } else {
                      // Send the token to your server.
                      stripeTokenHandler(result.token);
                    }
                  });
                });
            
                function stripeTokenHandler(token) {
                  // Insert the token ID into the form so it gets submitted to the server
                  var form = document.getElementById('payment-form');
                  var hiddenInput = document.createElement('input');
                  hiddenInput.setAttribute('type', 'hidden');
                  hiddenInput.setAttribute('name', 'stripeToken');
                  hiddenInput.setAttribute('value', token.id);
                  form.appendChild(hiddenInput);
            
                  // Submit the form
                  form.submit();
                }
              </script>
            
            <?
    }        
?>
<!-- footers -->
<?php require_once $abs_us_root.$us_url_root.'users/includes/html_footer.php'; ?>