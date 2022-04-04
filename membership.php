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
// $groupname = ucfirst($loggedInUser->title);
$raw = date_parse($user->data()->join_date);
$signupdate = $raw['month'].'/'.$raw['day'].'/'.$raw['year'];
$userdetails = fetchUserDetails(null, null, $get_info_id); //Fetch user details
$keys = $db->query("SELECT * FROM `plg_mem_stripe`")->first(); 
$stripeCustSub = $db->query("SELECT * FROM `plg_mem_stripe_custID` WHERE user = ?",[$user->data()->id])->first(); 
$stripe_customer = $stripeCustSub->stripe_customer;
$stripe_sub = $stripeCustSub->stripe_subscription;
?>

 

<div class="container">
<?php 
if(pluginActive('membership',true)){
$status = ""; 
global $user,$settings;
$memset = $db->query("SELECT * FROM plg_mem_settings")->first();
$status = memberPlanStatus();
$extend = Input::get('extend');
$memSettings = $db->query("SELECT * FROM plg_mem_settings")->first();
$plans = $db->query("SELECT * FROM plg_mem_plans ORDER BY ordering")->results();
$ps = false;
$pl = Input::get('ps');
if(is_numeric($pl)){$ps = true;}   
$sel = false;  
$membershipChange = Input::get('change');
$userdetails=$user->data(); 

$opt = Input::get('opt'); 
$pass = false;
$plan = Input::get('plan');
$cost = Input::get('plan_cst');
$subChange = Input::get('subscription');
$getPayment = Input::get('payment');



                    
// ****** SUBSCRIPTION MADE, PROCCESSING CODE
	if(!empty($_POST && $membershipChange == "membership" && $opt == "process" )){
	    if ($user->data()->plg_mem_exp = NULL){
	        
	    }
 		require_once($abs_us_root.$us_url_root.'usersc/plugins/membership/vendor/autoload.php');
                    //setting important variables
                    $name = $_POST['cname'];
                    $email = $_POST['email'];
                    $token = $_POST['stripeToken'];
                    $stripe_price = $_POST['stripe_price'];
                    $stripe = new \Stripe\StripeClient($keys->stripe_s);
                    
                    //create customer
                    $customer = $stripe->customers->create([
                    'name' => $name,
                    'email' => $email,
                    'source' => $token
                    ]);
                    
                    $customerid = $customer->id;
                    
              
                    //create subscription, (Request POST body to stripe)
                    $subscription = $stripe->subscriptions->create([
                    'customer' => $customerid,
                    'items' =>[
                        ['price' => $stripe_price]
                      ],
                      //this meta data is important so the webhook works
                    'metadata' => [
                            'purchase_meta' => $user->data()->id,
                            'plan' => $plan,
                            'status' => $status, 
                            'cost' => $cost,
                            'exp' => $user->data()->plg_mem_exp,
                            'change' =>$user->data()->plg_mem_level,
                            'via' => 'stripe',
                          ]
                    ]);
                    
                    
                    if($subscription->status == 'active'){
                        //woot woot, it works 
                        header("Location: membership.php?&display=success");
                    }else {
                      echo ' problema sir';
                    }
	}



// ******* CHECKOUT CODE
	if(!empty($_POST && $membershipChange == "membership" && $opt == "checkout" )){
		   $po = Input::get('paymentOption');
		   
		   if($status == "Active" && $plan != $user->data()->plg_mem_level){
		         Redirect::to('account.php?change=membership&err=Only+an+admin+can+change+your+plan');
		   } 
		   
		   $cost = Input::get('plan_cst');
		   $check1 = $db->query("SELECT * FROM plg_mem_plans WHERE disabled = 0 AND id = ?",[$plan])->count();
	           $check2Q = $db->query("SELECT * FROM plg_mem_cost WHERE id = ? AND plan = ? AND disabled = 0",[$cost,$plan]);
		   $check2C = $check2Q->count();
		
		   if($check1 < 1 || $check2C < 1){
			Redirect::to('account.php?change=membership&err=Invalid+plan+selected');
		   }
		
		   $check2 = $check2Q->first();
		   $check3Q = $db->query("SELECT * FROM plg_mem_plans WHERE disabled = 0 AND id = ?",[$plan]); 
		   $check3 = $check3Q->first();
		   $pass = true;
	}
	
	
// ****** SUBSCRIPTION CANCELLED ;c
     	if($subChange == "unsubscribe"){
     	    $keys = $db->query("SELECT * FROM `plg_mem_stripe`")->first(); 
     	    require_once($abs_us_root.$us_url_root.'usersc/plugins/membership/vendor/autoload.php');
     	    $stripe = new \Stripe\StripeClient($keys->stripe_s);
     	    $subscription = $stripe->subscriptions->retrieve($stripe_sub);
         	    if($subscription->status == "active"){
         	    $subscription = $stripe->subscriptions->cancel($stripe_sub);
         	    echo "
             	    <div class='card mb-4 py-3 border-bottom-success text-center'>
                        <div class='card-body'>
                             You successfully unsubscribed! ;c
                        </div>
                    </div></div></div>
                    </div>
         	    ";
            	}else{
            	    header("Location: membership.php");
            	}
     	}
     	
// ******  SUBSCRIPTION UPDATED	
     	if($subChange == "updateProcess" && $_POST){
     	    require_once($abs_us_root.$us_url_root.'usersc/plugins/membership/vendor/autoload.php');
     	    $stripe = new \Stripe\StripeClient($keys->stripe_s);
     	    $plan = Input::get('plan');
     	    $cost = Input::get('plan_cst');
     	    $costs = $db->query("SELECT * FROM plg_mem_cost WHERE id = ?",[$cost])->first();
     	    $custID = $db->query("SELECT * FROM `plg_mem_stripe_custID` WHERE user = ?",[$user->data()->id])->first();
     	    $subscription = $stripe->subscriptions->retrieve($stripe_sub);
         	    if($subscription->status == "active"){
         	  // cancel current subscription      
         	    $cancel_sub = $stripe->subscriptions->cancel($stripe_sub);
         	  //create new subscription
         	    $subscription = $stripe->subscriptions->create([
                    'customer' => $custID->stripe_customer,
                    'items' =>[
                        ['price' => $costs->stripe_priceID, ]
                      ],
                      //this meta data is important so the webhook works
                    'metadata' => [
                            'purchase_meta' => $user->data()->id,
                            'plan' => $plan,
                            'status' => $status, 
                            'cost' => $cost,
                            'exp' => $user->data()->plg_mem_exp,
                            'change' =>$user->data()->plg_mem_level,
                            'via' => 'stripe',
                          ]
                    ]);
         	     header("location: /");
         	    }
         	    if($subscription->status == "canceled"){
         	  //create new subscription
         	    $subscription = $stripe->subscriptions->create([
                    'customer' => $custID->stripe_customer,
                    'items' =>[
                        ['price' => $costs->stripe_priceID, ]
                      ],
                      //this meta data is important so the webhook works
                    'metadata' => [
                            'purchase_meta' => $user->data()->id,
                            'plan' => $plan,
                            'status' => $status, 
                            'cost' => $cost,
                            'exp' => $user->data()->plg_mem_exp,
                            'change' =>$user->data()->plg_mem_level,
                            'via' => 'stripe',
                          ]
                    ]);
                     header("location: /"); 
         	    
         	    }
     	}
     	
// ******  ADD A PAYMENT     	
     	if($getPayment == "add" && $_POST){
            $token = $_POST['stripeToken'];
            $custID = $db->query("SELECT * FROM `plg_mem_stripe_custID` WHERE user = ?",[$user->data()->id])->first();
            require_once($abs_us_root.$us_url_root.'usersc/plugins/membership/vendor/autoload.php');
     	    $stripe = new \Stripe\StripeClient($keys->stripe_s);
            $stripe->customers->createSource($custID->stripe_customer,['source' => $token]);
            header("location: /"); 
     	}
     	
// ******  SET DEFAULT PAYMENT	
        if($getPayment == "setDefault"){
            $custID = $db->query("SELECT * FROM `plg_mem_stripe_custID` WHERE user = ?",[$user->data()->id])->first();
            require_once($abs_us_root.$us_url_root.'usersc/plugins/membership/vendor/autoload.php');
     	    $stripe = new \Stripe\StripeClient($keys->stripe_s);
     	    $stripe->customers->update($custID->stripe_customer,['default_source' => (Input::get('id')) ]);
            header("location: /"); 
     	}
     	
// ******  DELETE CARD	
        if($getPayment == "delete"){
            $custID = $db->query("SELECT * FROM `plg_mem_stripe_custID` WHERE user = ?",[$user->data()->id])->first();
            require_once($abs_us_root.$us_url_root.'usersc/plugins/membership/vendor/autoload.php');
     	    $stripe = new \Stripe\StripeClient($keys->stripe_s);
     	    $stripe->customers->deleteSource($custID->stripe_customer,(Input::get('id')),[]);
            header("location: /"); 
     	}
?>

	<!-- a bunch of css that can be stored elsewhere -->
		<style>
		@import url('https://fonts.googleapis.com/css?family=Raleway&display=swap');
		:root {
		  --light-grey: #F6F9FC;
		  --dark-terminal-color: #0A2540;
		  --accent-color: #635BFF;
		  --radius: 3px;
		}
		form > * {
		  margin: 10px 0;
		}
		button {
		  background-color: var(--accent-color);
		}
		button {
		  background: #1bb1dc;
		  border-radius: 3px;
		  color: white;
		  border: 0;
		  padding: 12px 16px;
		  margin-top: 16px;
		  font-weight: 600;
		  cursor: pointer;
		  transition: all 0.2s ease;
		  display: block;
		}
		button:hover {
		  filter: contrast(115%);
		}
		button:active {
		  transform: translateY(0px) scale(0.98);
		  filter: brightness(0.9);
		}
		button:disabled {
		  opacity: 0.5;
		  cursor: none;
		}
		input,  {
		  display: block;
		  font-size: 1.1em;
		  width: 100%;
		}
		.titleCost {
		  font-weight: 700;
		  margin-bottom: 15px;
		  font-size:2em;
		  color:#555;
		}
		#messages {
		  font-family: source-code-pro, Menlo, Monaco, Consolas, 'Courier New';
		  display: none; /* hide initially, then show once the first message arrives */
		  background-color: #0A253C;
		  color: #00D924;
		  padding: 20px;
		  margin: 20px 0;
		  border-radius: 3px;
		  font-size:0.7em;
		}
        hr {
            color: #333;
            overflow: visible;
            text-align: center;
            height: 5px;
            font-size:0.7rem;
        }
        .hr hr:after {
            background: #fff;
            color: #666;
            content: 'Pay with Card';
            padding: 0 4px;
            position: relative;
            top: -9px;
        }
        /*important stuff here*/
        * {
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            -webkit-text-size-adjust: none;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
            Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            font-size: 15px;
            line-height: 1.4em;
        }
                
        /* Checkout */
        #checkout {
            max-width: 480px;
            margin: 0 auto;
            transition: visibility 0s, opacity 0.5s linear 0.5s;
        }
        #main.checkout #checkout {
            visibility: visible;
            opacity: 1;
        }
        section {
            display: flex;
            flex-direction: column;
            position: relative;
            text-align: left;
        }
        h1 {
            margin: 0 0 20px 0;
            font-size: 20px;
            font-weight: 500;
        }
        h2 {
            margin: 15px 0;
            color: #32325d;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 13px;
            font-weight: 500;
        }
                
        /* Form */
                
        fieldset {
            margin-bottom: 20px;
            background: #fff;
            box-shadow: 0 1px 3px 0 rgba(50, 50, 93, 0.15),
            0 4px 6px 0 rgba(112, 157, 199, 0.15);
            border-radius: 4px;
            border: none;
            font-size: 0;
        }
        fieldset label {
            position: relative;
            display: flex;
            flex-direction: row;
            height: 42px;
            padding: 10px 0;
            align-items: center;
            justify-content: center;
            color: #8898aa;
            font-weight: 400;
        }
        fieldset label:not(:last-child) {
            border-bottom: 1px solid #f0f5fa;
        }
        fieldset label.state {
            display: inline-flex;
            width: 75%;
        }
        fieldset:not(.with-state) label.state {
            display: none;
        }
        fieldset label.zip {
            display: inline-flex;
            width: 100%;
        }
        fieldset:not(.with-state) label.zip {
            width: 100%;
        }
        fieldset label span {
            min-width: 125px;
            padding: 0 15px;
            text-align: right;
        }
        fieldset .redirect label span {
            width: 100%;
            text-align: center;
        }
        .field {
            flex: 1;
            padding: 0 15px;
            background: transparent;
            font-weight: 400;
            color: #31325f;
            outline: none;
            cursor: text;
        }
        .field::-webkit-input-placeholder {
            color: #aab7c4;
        }
        .field::-moz-placeholder {
            color: #aab7c4;
        }
        .field:-ms-input-placeholder {
            color: #aab7c4;
        }
        input {
            flex: 1;
            border-style: none;
            outline: none;
            color: #313b3f;
        }
        ::-webkit-input-placeholder {
            color: #cfd7e0;
        }
        ::-moz-placeholder {
            color: #cfd7e0;
            opacity: 1;
        }
        :-ms-input-placeholder {
            color: #cfd7e0;
        }
        input:-webkit-autofill,
        select:-webkit-autofill {
        -webkit-text-fill-color: #666ee8;
        transition: background-color 100000000s;
        -webkit-animation: 1ms void-animation-out 1s;
        }
        .StripeElement--webkit-autofill {
            background: transparent !important;
        }
        #card-element {
            margin-top: -1px;
            width: 100%;
        }
        #ideal-bank-element {
            padding: 0;
        }
        button {
            display: block;
            background: #1bb1dc;
            color: #fff;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            border: 0;
            font-weight: 700;
            width: 100%;
            height: 40px;
            outline: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        button:focus {
            background: #555abf;
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 7px 14px 0 rgba(50, 50, 93, 0.1),
            0 3px 6px 0 rgba(0, 0, 0, 0.08);
        }
        button:active {
            background: #43458b;
        }
        .card-number {
            padding-left: 8px;
            white-space: nowrap;
            font-family: Source Code Pro, monospace;
            color: #0d2b3e;
            font-weight: 500;
        }
        .card-number span {
            display: inline-block;
            width: 8px;
        }
        #payment-request-button iframe {
            border-radius: 4px;
            border: 0;
        }
        @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap');

        :root {
            --back: #efeef4;
            --theme: #666;
        }
        .background {
            display: grid;
            place-items: center;
        }
        .background .paper {
            width: 400px;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
        }
        .background .header-title {
            font-size: 20px;
            font-weight: 700;
        }
        .background .header-title span {
            font-weight: 400;
            opacity: 0.5;
        }
        .background  .radio-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px 0;
        }
        .background .lbl-radio {
            display: block;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 15px;
            padding-left: 50px;
            position: relative;
            cursor: pointer;
        }
        .background .lbl-radio .content .title {
            font-weight: 600;
            margin-bottom: 7px;
        }
        .background .lbl-radio .content .subtext {
            opacity: 0.5;
            font-size: 14px;
        }
        .background .marker {
            width: 15px;
            height: 15px;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            position: absolute;
            left: 20px;
            top: 40%;
        }
        .background input[type='radio']:checked + .lbl-radio {
            border-color: var(--theme);
        }
        .background input[type='radio']:checked + .lbl-radio > .marker {
            border: 1px solid var(--theme);
        }
        .background input[type='radio'] {
        	display: none;
        }
</style>
<script src="https://js.stripe.com/v3/"></script> 
  
  
    
 <?php  
// ****** SUBSCRIPTION CHOICES
if($membershipChange == "membership" && !$pass){ ?>
 
  <form class="" action="membership.php?change=membership&opt=checkout" method="post">
    <div class="form-group">
        <div class="card border-0 shadow-lg pt-5 my-5 position-relative">
            <div class="card-body">
              
              <div class="card-text pt-1">
                <div class="mb-3 text-center" >
                  
                    <section class="background">
                        <div class="paper">
                            <header class="header-title">Membership <span></span></header>
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
                                    $costs = $db->query("SELECT * FROM plg_mem_cost WHERE disabled = '0' ORDER BY days ")->results();
                                    foreach($costs as $p){ ?>
                                    <!-- radio -->
                                        <input type="radio" name="plan_cst" id="<?=$p->id?>" data-plan="<?=$p->plan?>" value="<?=$p->id?>" required />
                                        <label for="<?=$p->id?>" class="lbl-radio">
                                          <div class="marker"></div>
                                          <div class="content">
                                            <div class="title"><?=$p->descrip?></div>
                                            <div class="subtext"><?=$memset->sym?><?=$p->cost?> every <?=$p->days?> Days</div>
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
if($membershipChange == "membership" && $pass && $opt == "checkout"){
$stripe_price = $db->query("SELECT * FROM plg_mem_cost WHERE id = ?",[$cost])->first();

?>
<br />
<br /><br />
<div class="container text-center">
    <div class="row">
        <div class="col-md-12">
                  <div class="row  align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-0"><?=$check3->plan_name?></div>
                        <div class="font-weight-bold text-uppercase mb-0 titleCost">$ <?=$check2->cost?></div>
                        <div class="text-xs  font-weight-bold text-secondary text-uppercase mb-0"><?=$check2->descrip?> </div>
                        <br />
                        <br /><br />
                    </div> 
                  </div>
        </div>    
        <br/><br />
        <div class="col-md-12 ">
                <div class="form-row">
                  <div class="col-sm-12">
                     <div id="payment-request-button">
                <!-- A Stripe Element will be inserted here if the browser supports this type of payment method. -->
                     </div>
                  </div>
                </div>
                    <br />
                    <div class="hr"><hr class="hr"/></div>
                     <div id="checkout">
                         <form  action="membership.php?change=membership&opt=process" method="post" id="payment-form">
                 
                           <section>
                            <fieldset class="with-state">
                                <label>
                                  <span>Name</span>
                                  <input name="cname" class="field" placeholder="Jenny Rosen" required>
                                </label>
                                <label>
                                  <span>Email</span>
                                  <input name="email" type="email" class="field" value="<?=$user->data()->email?>" readonly required>
                                </label>
                                </label>
                                 <div class="payment-info">
                                <input name="stripe_price" style="display: none;" class="planOption"  id="stripe_price"  value="<?=$stripe_price->stripe_priceID?>"> 
                                <input type="hidden" name="plan_cst" value="<?=$cost?>">
                                <input type="hidden" name="plan" value="<?=$plan?>">
                                <input type="hidden" name="paymentOption" value="<?=$po?>">   
                                <label>
                                    <span>Card</span>
                                    <div id="card-element" class="field"></div>
                                     <div id="card-errors" role="alert"></div>
                                </label>
                                 </div>
                            </fieldset>
                                  <button type="submit" class="col-sm-12" >Pay $ <?=$check2->cost?></button>
                         </section>
              </form> <br /><br />
        </div>
</div>
</div></div>
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
}//end tag
?>
<br/> 

<?php 
// Success screen, redirect buttons comming soon.
   $pn_success = Input::get('display');
   if($pn_success == "success"){
    include($abs_us_root.$us_url_root.'usersc/plugins/membership/hooks/_success.php');
   }


// ****** DEFUALT PAGE
        if($membershipChange == "" && $opt == "" &&  $subChange == "" && $getPayment == ""){
        ?>
        <h6>Current Plan | <span ><?=$status?></span></h6>
        <hr />
            <?php 
            if($status == "Active"){
                    require_once($abs_us_root.$us_url_root.'usersc/plugins/membership/vendor/autoload.php');
                    $stripe = new \Stripe\StripeClient($keys->stripe_s);
                    $subscription = $stripe->subscriptions->retrieve($stripe_sub);
                    $stripe_price = $subscription->items->data[0]->price->id;
                    
                    $priceInfo = $stripe->prices->retrieve($stripe_price,[]);
                    $priceDecimal = $priceInfo->unit_amount/pow(10,2);
                    
                    $invoiceLoop =  $stripe->invoices->all(['customer' => $stripe_customer]);
                    $cardLoop = $stripe->paymentMethods->all(['customer' => $stripe_customer, 'type' => 'card', ]);
                    
                    $default_payment = $stripe->customers->retrieve($stripe_customer,[]);
            ?>
            <div class="row">
                <div class="col-md-6">
                    <h3><strong><?=echoPlanName($user->data()->plg_mem_level);?></strong> </h3><br>
                    <h6>Autorenewal : <span class="text-capitalize"><?=$subscription->status?></span></h6>
                    <h6><?=$memset->sym?> <?= $priceDecimal?>
                        <span class="text-uppercase"><?=$priceInfo->currency?> /</span>
                        <span class="text-capitalize"> <?=$priceInfo->recurring->interval?></span>
                    </h6>
                    <h6>Membership Good Until : <?=$user->data()->plg_mem_exp;?> </h6>
                </div>
                <div class="col-md-6">  
                    <a href="?subscription=update"><button type="button" class="btn btn-primary btn-lg">Update Plan</button></a>
                        <br />
                    <a href="?subscription=unsubscribe"><button type="button" class="btn btn-light btn-lg">Cancel Plan</button></a>
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
                    foreach ($invoiceLoop as $rslt) {
                        $epoch = $rslt->lines->data[0]->period->start;
                        $dt = new DateTime("@$epoch");  // convert UNIX timestamp to PHP DateTime
                        echo "
                         <tr>
                          <td>".$rslt->lines->data[0]->description."</td>
                          <td>".$dt->format('m-d-Y')."</td>
                          <td class='text-capitalize'>".$rslt->status."</td>
                          <td><a href='".$rslt->hosted_invoice_url."'>View <span class='glyphicon glyphicon-new-window'></span></a></td>
                        </tr>
                        ";
                    }
                    ?>
                        </thead>
                </table>
                    
                </div>
            
            <?} else { 
                //take them to create membership and a customer id with stripe.
                header("Location: membership.php?change=membership"); }
        }


// ****** UPDATE SUBSCRIPTION
        if($membershipChange == "" && $opt == "" &&  $subChange == "update" ){?>
        <form class="" action="membership.php?subscription=updateProcess" method="post">
            <div class="card-body">
              <div class="member-profile position-absolute w-100 text-center">
                  
                </div>
              <div class="card-text pt-1">
                   <div class="card-text pt-1">
                <div class="mb-3 text-center" >
                  
                    <section class="background">
                        <div class="paper">
                            <header class="header-title">Membership <span></span></header>
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
                        
                        <style>
                        
                        </style>
                        <?// select the pricing ?>
                        <section class="background">
                            <div class="paper">
                              <header class="header-title">Choose service <span>Subscription</span></header>
                              <aside class="radio-container">
                                    <?php
                                    $costs = $db->query("SELECT * FROM plg_mem_cost WHERE disabled = '0' ORDER BY days ")->results();
                                    foreach($costs as $p){ ?>
                                    <!-- radio -->
                                        <input type="radio" name="plan_cst" id="<?=$p->id?>" data-plan="<?=$p->plan?>" value="<?=$p->id?>" required />
                                        <label for="<?=$p->id?>" class="lbl-radio">
                                          <div class="marker"></div>
                                          <div class="content">
                                            <div class="title"><?=$p->descrip?></div>
                                            <div class="subtext"><?=$memset->sym?><?=$p->cost?> every <?=$p->days?> Days</div>
                                          </div>
                                        </label>
                                    <?php   } ?>
                                    
                                  <!--  </select>-->
                                    </div>
                                </aside>
                            </div>
                        </section>
                                <input name="paymentOption" required  style="display: none;" id="paymentOption"  value="stripe"/>
                        
             </div></div></div>
            </div><!--card-body-->
            <div class="card-footer theme-bg-primary border-0 text-center">
              <ul class="social-list list-inline mb-0 mx-auto">
                    <li class="list-inline-item">
                    <input type="submit" value="Select Plan" id="subBtn" class="btn  btn-main-main"></li>
                  </ul><!--social-list-->
            </div><!--card-footer-->    
        <?
        }
        
        
        
// ****** EDIT PAYMENT
        if($getPayment == "add"){ ?>
            <div class="container text-center">
                <div class="row">
                     <div class="col-md-12">
                              <div class="row  align-items-center">
                                <div class="col mr-2">
                                    <br />
                                    <div class="text-xs  font-weight-bold text-secondary text-uppercase mb-0">Add Payment </div>
                                    <br />
                                </div> 
                              </div>
                    </div>      
                    <br/><br />
                    <div class="col-md-12 ">
                            <div class="form-row">
                              <div class="col-sm-12">
                                 <div id="payment-request-button">
                            <!-- A Stripe Element will be inserted here if the browser supports this type of payment method. -->
                                 </div>
                              </div>
                            </div>
                                <br />
                               
                                 
                                 <div id="checkout">
                                     <form  action="" method="post" id="payment-form">
                             
                                         <section>
                                          <fieldset class="with-state">
                                            
                                             <div class="payment-info">
                                  <label>
                                    <span>Card</span>
                                    <div id="card-element" class="field"></div>
                                     <div id="card-errors" role="alert"></div>
                                  </label>
                              </div>
                                          </fieldset>
                                          
                                              <button type="submit" class="col-sm-12" >Add Payment</button>
                              
                                     </section>
                          </form>
                                <br /><br />
                    </div>
            
            </div>
            </div></div>
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