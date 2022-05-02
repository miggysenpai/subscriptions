<?php
/*
UserSpice 5
An Open Source PHP User Management System
by the UserSpice Team at http://UserSpice.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
require_once '../../../users/init.php';
require_once $abs_us_root.$us_url_root.'users/includes/template/prep.php';
include "plugin_info.php";
pluginActive($plugin_name);
if (!securePage($_SERVER['PHP_SELF'])){die();}
$keys = $db->query("SELECT * FROM `plg_sub_stripe`")->first(); 
require_once($abs_us_root.$us_url_root.'usersc/plugins/subscriptions/vendor/autoload.php');
$stripe = new \Stripe\StripeClient($keys->stripe_s);
$stripe_page = Input::get('page');
?>
 <style>
    .card {
        margin-bottom: 30px;
        border: none;
        border-radius: 5px;
        box-shadow: 0px 0 30px rgba(1, 41, 112, 0.1);
    }
                            
    .card-title {
        padding: 20px 0 15px 0;
        font-size: 18px;
        font-weight: 500;
        color: #222;
        font-family: "Poppins", sans-serif;
    }
    .card-title span {
        color: #222;
        font-size: 14px;
        font-weight: 400;
    }
    .card-body {
        padding: 0 20px 20px 20px;
    }
    main {
        background:#1bb1dc;
    }
</style>
			        
<div id="page-wrapper">
	<div class="container">
		<div class="row">
		    <div class="col-sm-12">
		        <button type="button" onclick="window.location.href = '<?=$us_url_root?>users/admin.php?view=plugins_config&plugin=subscriptions';" 
                name="button" class="btn btn-primary">Configure Subscriptions</button>
		        
		    </div>
			<div class="col-sm-12">
			  
			  <?php
			  //Stripe Subscription
			  if($stripe_page == "subscriptions"){
			  $sub_stripe = $stripe->subscriptions->all([]);
			  // Remove to view JSON LIST ARRAY   
			  // print("<pre>".print_r($sub_stripe,true)."</pre>");
			  ?>
			    <br />
    			    <table class="table table-striped ">
    			     <thead>
                        <tr>
                          <th scope="col">User ID</th>
                          <th scope="col">Sub ID</th>
                          <th scope="col">Customer ID</th>
                          <th scope="col">Recurring</th>
                        </tr>
                      </thead>   
                    <tbody>
                     <?php
                    foreach ($sub_stripe as $sub) {
                            echo "
                             <tr>
                              <td class=''>".$sub->metadata->purchase_meta." </td>
                              <td class=''>".$sub->id." </td> 
                              <td class=''>".$sub->customer." </td>
                              <td class='text-capitalize'>".$sub->status." </td>
                            </tr>
                            ";
                        }
                     ?>
                    </tbody>
                 </table>
			  <?php }?>   
			  
			  
			  <?php
			  //Stripe Products
			  if($stripe_page == "products"){
			  $price_stripe = $stripe->prices->all([]);
			  // Remove to view JSON LIST ARRAY   
			  // print("<pre>".print_r($price_stripe,true)."</pre>");
			    ?>
			    <br />
			    <table class="table table-striped">
    			     <thead>
                        <tr>
                          <th scope="col">Price ID</th>
                          <th scope="col">Product ID</th>
                          <th scope="col">Description</th>
                        </tr>
                      </thead>   
                    <tbody>
                     <?php
                    foreach ($price_stripe as $prc) {
                        $priceDecimal = $prc->unit_amount/pow(10,2);
                            echo "
                            
                             <tr>
                              <td class=''>".$prc->id." </td>
                              <td class=''>".$prc->product." </td>
                              <td>
                                    <span class='text-capitalize'>".$priceDecimal." / ".$prc->recurring->interval."</span>
                                    <span class='text-uppercase'> ".$prc->currency." </span>
                              </td>
                            </tr>
                            ";
                        }
                     ?>
                    </tbody>
                 </table>
			  <?php }?> 
			  
			  
			  <?php 
			  //Stripe Customers
			  if($stripe_page == "customers"){
			  $customer_stripe = $stripe->customers->all([]);
			  // Remove to view JSON LIST ARRAY 
			  // print("<pre>".print_r($customer_stripe,true)."</pre>");
			    ?>
			    <br />
    			    <table class="table table-striped">
        			     <thead>
                            <tr>
                              <th scope="col">Customer ID</th>
                              <th scope="col">Email</th>
                              <th scope="col">Card Info</th>
                            </tr>
                          </thead>   
                        <tbody>
                         <?php 
                        foreach ($customer_stripe as $cust) {
                                echo "
                                
                                 <tr>
                                  <td class=''>".$cust->id." </td>
                                  <td class=''>".$cust->email." </td>
                                  <td class=''>".$cust->default_source." </td>
                                 
                                </tr>
                                ";
                            }
                         ?>
                        </tbody>
                    </table>
			  <?php }?> 
			  
			  
			  <?php
			  //Stripe Transactions
			  if($stripe_page == "transactions"){
			  $transactions_stripe = $stripe->charges->all([]);
			  // Remove to view JSON LIST ARRAY 
			  // print("<pre>".print_r($transactions_stripe,true)."</pre>");
			    ?>
			    <br />
    			    <table class="table table-striped">
        			     <thead>
                            <tr>
                              <th scope="col">Charge ID</th>
                              <th scope="col">Amount</th>
                              <th scope="col">Customer</th>
                              <th scope="col">Email</th>
                              <th scope="col">Refund</th>
                            </tr>
                          </thead>   
                        <tbody>
                         <?php
                        foreach ($transactions_stripe as $trans) {
                                echo "
                                 <tr>
                                  <td class=''>".$trans->id." </td>
                                  <td class=''>".$trans->amount." </td>
                                  <td class=''>".$trans->customer." </td>
                                  <td class=''>".$trans->receipt_email." </td>
                                  <td class=''>";
                                  if ($trans->refunded == true){
                                      echo "Refunded";
                                  } else {
                                      echo "<a href='?page=refundsConfirm&charge=".$trans->id."' <i class='fa fa-reply ' aria-hidden='true'></i>";
                                  }
                                  echo "
                                    </td>
                                </tr>
                                ";
                            }
                         ?>
                        </tbody>
                    </table>
			  <?php }?> 
			  
			  
			  <?php 
			  //Stripe Transactions
			  if($stripe_page == "payouts"){
			  $payouts_stripe = $stripe->payouts->all([]);
			  // Remove to view JSON LIST ARRAY  
			  // print("<pre>".print_r($payouts_stripe,true)."</pre>");
			    ?>
			    <br />
    			    <table class="table table-striped">
        			     <thead>
                            <tr>
                              <th scope="col">Payout ID</th>
                              <th scope="col">Amount</th>
                              <th scope="col">Destination</th>
                              <th scope="col">Created</th>
                              <th scope="col">Status</th>
                            </tr>
                          </thead>   
                        <tbody>
                         <?php
                        foreach ($payouts_stripe as $pay) {
                                $epochDate = date("m-d-Y", substr($pay->created, 0, 10));
                                echo "
                                 <tr>
                                  <td class=''>".$pay->id." </td>
                                  <td class=''>".$pay->amount." </td>
                                  <td class=''>".$pay->destination." </td>
                                  <td class=''>".$epochDate." </td>
                                  <td class='text-capitalize'>".$pay->status." </td>
                                 
                                </tr>
                                ";
                            }
                         ?>
                        </tbody>
                    </table>
			  <?php }?>
			  
			  
			  <?php
			  //Stripe Transactions
			  if($stripe_page == "refunds"){
			  $refunds_stripe = $stripe->refunds->all([]);
			  // Remove to view JSON LIST ARRAY 
			  // print("<pre>".print_r($refunds_stripe,true)."</pre>");
			    ?>
			    <br />
    			    <table class="table table-striped">
        			     <thead>
                            <tr>
                              <th scope="col">Refund ID</th>
                              <th scope="col">Charge ID</th>
                              <th scope="col">Amount</th>
                              <th scope="col">Status</th>
                            </tr>
                          </thead>   
                        <tbody>
                         <?php 
                        foreach ($refunds_stripe as $refund) {
                                echo "
                                 <tr>
                                  <td class=''>".$refund->id." </td>
                                  <td class=''>".$refund->amount." </td>
                                  <td class=''>".$refund->charge." </td>
                                  <td class='text-capitalize'>".$refund->status." </td>
                                </tr>
                                ";
                            }
                         ?>
                        </tbody>
                    </table>
			  <?php }?> 
			  
			  
			  <?php 
			  //Stripe Refunds Confirm
			  if($stripe_page == "refundsConfirm"){
			    $stripe_charge = Input::get('charge');
			    ?>
			    <br />
			       
			        <br /><br />
			         <section class=" section ">
                        <div class="container">
                          <div class="row justify-content-center">
                            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                              <div class="card mb-3">
                                <div class="card-body">
                                         <div class=" section-title text-center pb-0 h2 text-primary"  data-aos="fade-up">
                                             <br />
                                        <p>Refund</p>
                                      </div>
                                    <h5 class="card-title text-center pb-0 ">Confirm Refund</h5>
                                    <p class="text-center small">Click confirm to give refund</p>
                                  
                                <form class="form-signin row g-3" action="?page=refundsConfirm" method="post"  >
            
                                    <div class="col-12">
                                      <label for="charge" class="form-label" >Charge ID</label>
                                      <div class="input-group">
                                        <input type="text" name="charge" id="charge" class="form-control" value="<?=$stripe_charge?>" required>
                                      </div>
                                      <br />
                                    </div>
                
                                    <div class="col-12">
                                      <button class="btn btn-primary w-100" id="next_button" type="submit">Confirm</button>
                                    </div>
                                  </form>
                
                                </div>
                              </div>
                
                            </div>
                          </div>
                        </div>
                                               
                      </section>
			  <?php }?>
			  
			  
			  <?php 
			  //Stripe Refunds Confirm POST
			  if($stripe_page == "refundsConfirm" && $_POST){
			    $stripe_charge = Input::get('charge');
			    $stripe->refunds->create(['charge' => $stripe_charge]);
			  ?>
			        <section class="section ">
                        <div class="container">
                          <div class="row justify-content-center">
                            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                              <div class="card mb-3">
                                <div class="card-body">
                                         <div class=" section-title text-center pb-0 h2 text-primary"  data-aos="fade-up">
                                      </div>
                                    <h5 class="card-title text-center pb-0 ">Refund Confirmed</h5>
                                    <p class="text-center small">Redirecting to refunds page.
                                        <br />
                                         <div class="d-flex justify-content-center">
                                          <div class="spinner-border" role="status">
                                            <span class="sr-only">Redirecting...</span>
                                          </div>
                                        </div>
                                    </p>
                                   
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>                
                      </section>
			    <?php
			    header( "refresh:5;url=?page=refunds" );
			    }?>
			    
			    
			    <?php
    			//Stripe Coupons
    			if($stripe_page == "coupons"){
    			$coupons_stripe = $stripe->coupons->all([]);
    			// Remove to view JSON LIST ARRAY   
    			// print("<pre>".print_r($coupons_stripe,true)."</pre>");
                    if($keys->stripe_coupons == true){
                        $text_code = 'success';                          
                    } else { $text_code = 'danger'; }
                                
			    ?>
			    <br />
			    <div class=" text-right" >
			        <button type="button" onclick="window.location.href = '?page=couponsOnOff';" 
                    name="button" class="btn btn-<?=$text_code?>">Coupons On/Off</button>
                    
    		        <button type="button" onclick="window.location.href = '?page=couponsADD';" 
                    name="button" class="btn btn-primary">Add Coupon</button>
    		    </div>
			    <br />
    			    <table class="table table-striped">
        			     <thead>
                            <tr>
                              <th scope="col">Coupon ID</th>
                              <th scope="col">Percent off</th>
                              <th scope="col">Reedemed</th>
                              <th scope="col">View</th>
                              <th scope="col">Delete</th>
                            </tr>
                          </thead>   
                        <tbody>
                         <?php
                            foreach ($coupons_stripe as $coupon) {
                                    echo "
                                     <tr>
                                      <td class=''>".$coupon->id." </td>
                                      <td class=''>".$coupon->percent_off." </td>
                                      <td class='text-capitalize'>".$coupon->times_redeemed." </td>
                                      <td class='text-capitalize'><a href='?page=couponView&coupon=".$coupon->id."'><span class='fa fa-eye'></a></span> </td>
                                      <td class='text-capitalize'><a href='?page=couponDelete&coupon=".$coupon->id."'><span class='fa fa-times-circle-o'></span></a></td>
                                    </tr>
                                    ";
                                }
                              ?>
                          </tbody>
                       </table>
			    <?php }?>
			    
			    
			    <?php
    			  //Stripe Coupon On/Off
    			  if($stripe_page == "couponsOnOff"){
    			      if($keys->stripe_coupons == true){
                        $text_code = 'success'; $text_code1 = 'checked'; $text_code2 = '';
                    } else { $text_code = 'danger'; $text_code2 = 'checked'; $text_code1 = '';}
    			      
    			    ?>
    			    <br />
    			       
    			        <br /><br />
    			         <section class="section ">
                            <div class="container">
                              <div class="row justify-content-center">
                                <div class="col-lg-6 col-md-12 d-flex flex-column align-items-center justify-content-center">
                                  <div class="card mb-3">
                                    <div class="card-body">
                                             <div class=" section-title text-center pb-0 h2 text-primary"  data-aos="fade-up">
                                                 <br />
                                            <p>Coupon</p>
                                          </div>
                                        <h5 class="card-title text-center pb-0 ">Turn Coupons On/Off</h5>
                                        <p class="text-center small">Turning this setting on will allow a coupon at checkout.</a></p>
                                        <form action="?page=couponsOnOffPOST" method="post">
                                            <br />
                                                <div class="custom-control custom-radio custom-control-inline">
                                                  <input type="radio" id="coupons_on" name="stripe_coupon" class="custom-control-input" value="1" <?=$text_code1?>>
                                                  <label class="custom-control-label" for="coupons_on" >On</label>
                                                </div>
                                                <div class="custom-control custom-radio custom-control-inline">
                                                  <input type="radio" id="coupons_off" name="stripe_coupon" class="custom-control-input" value="0" <?=$text_code2?>>
                                                  <label class="custom-control-label" for="coupons_off">Off</label>
                                                </div>
                                                
                                            <br/><br/>
                                            <button type="submit" class="btn btn-<?=$text_code?> btn-lg btn-block" type="submit">Update</button>
                                        </form>    
                                    </div>
                                    <br />
                                  </div>
                    
                                </div>
                              </div>
                            </div>
                                                   
                          </section>
    			<?php }?>
    			
    			
    			<?php
    			  //Stripe Coupons On/Off POST
    			  if($stripe_page == "couponsOnOffPOST" && $_POST){
    			      $stripe_coupon = Input::get('stripe_coupon');
                		$fields = array(
                			'stripe_coupons'=> $stripe_coupon,
                		);
                		
                	 $db->update('plg_sub_stripe', 1, $fields);
                		
    			  ?>
			        <section class="section ">
                        <div class="container">
                          <div class="row justify-content-center">
                            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                              <div class="card mb-3">
                                <div class="card-body">
                                         <div class=" section-title text-center pb-0 h2 text-primary"  data-aos="fade-up">
                                      </div>
                                    <h5 class="card-title text-center pb-0 ">Coupons Setting updated</h5>
                                    <p class="text-center small">Redirecting to coupons page.
                                        <br />
                                         <div class="d-flex justify-content-center">
                                          <div class="spinner-border" role="status">
                                            <span class="sr-only">Redirecting...</span>
                                          </div>
                                        </div>
                                    </p>
                                   
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>                
                      </section>
			    <?php
			    header( "refresh:5;url=?page=coupons" );
			    }?>
			    
			    
			    
			    <?php
    			  //Stripe Coupons ADD
    			  if($stripe_page == "couponsADD"){
    			    ?>
    			    <br />
    			    <script>
    			        function showRepeating(divId, element)
                            {
                                document.getElementById(divId).style.display = element.value == 'repeating' ? 'block' : 'none';
                            }
                        
                        function showRedemptions() {
                              var checkBox = document.getElementById("redemptionONOFF");
                              var text = document.getElementById("redemption");
                              if (checkBox.checked == true){
                                text.style.display = "block";
                              } else {
                                 text.style.display = "none";
                              }
                            }
    			    </script>
    			       
    			        <br /><br />
    			         <section class=" section ">
                            <div class="container">
                              <div class="row justify-content-center">
                                <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                                  <div class="card mb-3">
                                    <div class="card-body">
                                             <div class=" section-title text-center pb-0 h2 text-primary"  data-aos="fade-up">
                                                 <br />
                                            <p>Coupons</p>
                                          </div>
                                        <h5 class="card-title text-center pb-0 ">Add Coupons</h5>
                                        <p class="text-center small">More complex coupons can be created in Stripe with<a href="https://dashboard.stripe.com/coupons"> Coupon Management</a></p>
                                      
                                        <form class="form-signin row g-3" action="?page=couponsADDPOST" method="post" >
                                            <div class="col-12">
                                              <label for="id" class="form-label" >ID <i class="small">Name of coupon. Ex "SPRING20"</i></label>
                                              <div class="input-group">
                                                <input type="text" name="id" id="id" class="form-control" onkeyup="this.value = this.value.toUpperCase();" required>
                                              </div>
                                              <br />
                                            </div>
                                            
                                            <div class="col-12">
                                              <label for="percentOff" class="form-label" >Percent Off <i class="small">Ex "20" = 20% off (MAX 100)</i></label>
                                              <div class="input-group">
                                                <input type="number" name="percentOff" id="percentOff" class="form-control" max="100" required/>
                                              </div>
                                              <br />
                                            </div>
                                              
                                            <div class="col-12">
                                              <label for="duration" class="form-label" >Duration <i class="small">Specifies how long the discount will be in effect</i></label></label>
                                              <div class="input-group">
                                                 <select class="custom-select" id="duration" name="duration" onchange="showRepeating('repeating', this)">
                                                    <option value="once">Once</option>
                                                    <option value="forever">Forever</option>
                                                    <option value="repeating">Months</option>
                                                 </select>
                                              </div>
                                              <br />
                                            </div>
                                            
                                            <div id="repeating" style="display:none;" class="col-12">
                                              <label for="durationMonths" class="form-label" >Number of Months <i class="small"> Please specify how many months</i></label>
                                              <div class="input-group">
                                                <input type="number" name="durationMonths" id="durationMonths" class="form-control" >
                                              </div>
                                              <br />
                                            </div>
                                            
                                           <div class="col-12">
                                              <label for="" class="form-label" >Max redemptions</label>
                                              <div class="input-group custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="redemptionONOFF" name="redemptionONOFF" onclick="showRedemptions()">
                                                 <label class="custom-control-label" for="redemptionONOFF"></label>
                                              </div>
                                              <br />
                                            </div>
                                            
                                            
                                            <div id="redemption" style="display:none;" class="col-12">
                                              <label for="maxRedemtions" class="form-label" >Max redemptions</label>
                                              <div class="input-group">
                                                <input type="number" name="maxRedemtions" id="maxRedemtions" class="form-control">
                                              </div>
                                              <br />
                                            </div>
                        
                                            <div  class="col-12">
                                              <button class="btn btn-primary w-100" id="next_button" type="submit">Add Coupon</button>
                                            </div>
                                      </form>
                    
                                    </div>
                                  </div>
                    
                                </div>
                              </div>
                            </div>
                                                   
                          </section>
    			  <?php }?>
    			  
    			  
    			   <?php
    			  //Stripe Refunds Confirm POST
    			  if($stripe_page == "couponsADDPOST" && $_POST){
    			    $stripe_id = Input::get('id');
    			    $stripe_percentOff = Input::get('percentOff');
    			    $stripe_duration = Input::get('duration');
    			    $stripe_repeating = Input::get('durationMonths');
    			    $stripe_redemptionONOFF = Input::get('redemptionONOFF');
    			    $stripe_maxRedemtions = Input::get('maxRedemtions');
    			    
    			    $stripe_basic = [
    			        'id' => $stripe_id,  
                        'percent_off' => $stripe_percentOff,
                        ];
    			    
    			    if($stripe_duration == 'repeating'){
    			        $stripeduration = [
    			                'duration' => 'repeating',
    			                'duration_in_months' => $stripe_repeating ,
    			            ];
    			    } else {
    			        $stripeduration = [
    			                'duration' => $stripe_duration ,
    			            ];
    			    }
    			    if($stripe_redemptionONOFF == 'on'){
    			        $stripe_redemptions = [
    			                'max_redemptions' => $stripe_maxRedemtions ,
    			            ];
    			    } else {
    			        $stripe_redemptions = [];
    			    }
    			    
    			    $stripeCouponFields = 
    			        $stripe_basic +
                        $stripeduration +
                        $stripe_redemptions
    			       ;
    			    
    			    $stripe->coupons->create([
    			      $stripeCouponFields,
                        
                    ]);
                    
                    
    			  ?>
			        <section class="section ">
                        <div class="container">
                          <div class="row justify-content-center">
                            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                              <div class="card mb-3">
                                <div class="card-body">
                                         <div class=" section-title text-center pb-0 h2 text-primary"  data-aos="fade-up">
                                      </div>
                                    <h5 class="card-title text-center pb-0 ">Coupon Created</h5>
                                    <p class="text-center small">Redirecting to coupons page.
                                        <br />
                                         <div class="d-flex justify-content-center">
                                          <div class="spinner-border" role="status">
                                            <span class="sr-only">Redirecting...</span>
                                          </div>
                                        </div>
                                    </p>
                                   
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>                
                      </section>
			    <?php
			    header( "refresh:5;url=?page=coupons" );
			    }?>
			    
			    
			    <?php
    			  //Stripe Coupon View
    			  if($stripe_page == "couponView"){
    			      $stripe_coupon = Input::get('coupon');
    			      $coupon = $stripe->coupons->retrieve($stripe_coupon, []);
    			      // Remove to view JSON LIST ARRAY   
    			      // print("<pre>".print_r($coupon,true)."</pre>");
    			       $couponCreated = date("m-d-Y", substr($coupon->created, 0, 10));
    			    ?>
    			    <br />
    			    <style>
    			        a:hover{
    			            text-decoration:none;
    			        }
    			    </style>
    			       
    			        <br /><br />
    			         <section class="section ">
                            <div class="container">
                              <div class="row justify-content-center">
                                <div class="col-lg-6 col-md-12 d-flex flex-column align-items-center justify-content-center">
                                  <div class="card mb-3">
                                    <div class="card-body">
                                             <div class=" section-title text-center pb-0 h2 text-primary"  data-aos="fade-up">
                                                 <br />
                                            <p>Coupon</p>
                                          </div>
                                        <h5 class="card-title text-center pb-0 ">View Coupon</h5>
                                        <p class="text-center small">Coupons, by stripe design, cannot be edited.</a></p>
                                      
                                        <table class="table">
                                          <tbody>
                                            <tr>
                                              <td>ID</td>
                                              <td><?=$coupon->id?></td>
                                            </tr>
                                            <tr>
                                              <td>Created</td>
                                              <td><?=$couponCreated?></td>
                                            </tr>
                                            <?php if(isset($coupon->amount_off)){
                                                echo "
                                                <tr>
                                                  <td>Amount Off</td>
                                                  <td>".$coupon->amount_off."</td>
                                                </tr>
                                                ";
                                            }
                                            if(isset($coupon->amount_off)){
                                                echo "
                                                <tr>
                                                  <td>Currency</td>
                                                  <td>".$coupon->currency."</td>
                                                </tr>
                                                ";
                                            }
                                            ?>
                                            <tr>
                                              <td>Duration</td>
                                              <td class="text-capitalize"><?=$coupon->duration?></td>
                                            </tr>
                                            <?php if(isset($coupon->duration_in_months)){
                                                echo "
                                                <tr>
                                                  <td>Duration in months</td>
                                                  <td>".$coupon->duration_in_months."</td>
                                                </tr>
                                                ";
                                            }
                                            if(isset($coupon->max_redemptions)){
                                                echo "
                                                <tr>
                                                  <td>Max Redemptions</td>
                                                  <td>".$coupon->max_redemptions."</td>
                                                </tr>
                                                ";
                                            }
                                            if(isset($coupon->percent_off)){
                                                echo "
                                                <tr>
                                                  <td>Percent Off</td>
                                                  <td>".$coupon->percent_off."</td>
                                                </tr>
                                                ";
                                            }
                                            if(isset($coupon->redeem_by)){
                                                $couponRedeemBy = date("m-d-Y", substr($coupon->redeem_by, 0, 10));
                                                echo "
                                                <tr>
                                                  <td>Redeem By</td>
                                                  <td>".$couponRedeemBy."</td>
                                                </tr>
                                                ";
                                            }
                                            ?>
                                            <tr>
                                              <td>Times Redeemed</td>
                                              <td><?=$coupon->times_redeemed?></td>
                                            </tr>
                                            <tr>
                                              <td>Valid</td>
                                              <td><?=$coupon->valid?></td>
                                            </tr>
                                          </tbody>
                                          
                                        </table>
                                        
                    
                                    </div>
                                    <div class="container ">
                                        <a href="?page=couponDelete&coupon=<?=$coupon->id?>"><button class="btn btn-danger btn-lg btn-block" type="submit">Delete</button></a>
                                    </div>
                                    <br />
                                  </div>
                    
                                </div>
                              </div>
                            </div>
                                                   
                          </section>
    			  <?php }?>
    			  
    			  
    			  <?php
			  //Stripe Coupon Delete Confirm
			  if($stripe_page == "couponDelete"){
			    $stripe_coupon = Input::get('coupon');
			    ?>
			    <br />
			       
			        <br /><br />
			         <section class=" section ">
                        <div class="container">
                          <div class="row justify-content-center">
                            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                              <div class="card mb-3">
                                <div class="card-body">
                                         <div class=" section-title text-center pb-0 h2 text-primary"  data-aos="fade-up">
                                             <br />
                                        <p>Coupon</p>
                                      </div>
                                    <h5 class="card-title text-center pb-0 ">Click Confirm to Delete</h5>
                                    <p class="text-center small"> Deleting coupon does not affect
                                    customers who have applied coupon; deleting just means that it can no longer be redeemed. </p>
                                  
                                <form class="form-signin row g-3" action="?page=couponDelete" method="post">
            
                                    <div class="col-12">
                                      <label for="charge" class="form-label" >Coupon ID</label>
                                      <div class="input-group">
                                        <input type="text" name="coupon" id="coupon" class="form-control" value="<?=$stripe_coupon?>" required>
                                      </div>
                                      <br />
                                    </div>
                
                                    <div class="col-12">
                                      <button class="btn btn-danger w-100" id="next_button" type="submit">Confirm</button>
                                    </div>
                                  </form>
                
                                </div>
                              </div>
                
                            </div>
                          </div>
                        </div>
                                               
                      </section>
			  <?php }?>
			  
			  
			  <?php
			  //Stripe Coupon Deltete POST
			  if($stripe_page == "couponDelete" && $_POST){
			    $stripe_coupon = Input::get('coupon');
			    $stripe->coupons->delete($stripe_coupon, []);
			  ?>
			        <section class="section ">
                        <div class="container">
                          <div class="row justify-content-center">
                            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                              <div class="card mb-3">
                                <div class="card-body">
                                         <div class=" section-title text-center pb-0 h2 text-primary"  data-aos="fade-up">
                                      </div>
                                    <h5 class="card-title text-center pb-0 ">Delete Confirmed</h5>
                                    <p class="text-center small">Redirecting to Coupons page.
                                        <br />
                                         <div class="d-flex justify-content-center">
                                          <div class="spinner-border" role="status">
                                            <span class="sr-only">Redirecting...</span>
                                          </div>
                                        </div>
                                    </p>
                                   
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>                
                      </section>
			    <?php
			    header( "refresh:5;url=?page=coupons" );
			    }?>
			    
			    
			     <?php
			  //Stripe Coupon Deltete POST
			  if($stripe_page == "invoices"){
			     $invoiceLoop =  $stripe->invoices->all([]);
			      //print("<pre>".print_r($invoiceLoop,true)."</pre>");
			  ?>
			  <br /><br />
			        <section class="section ">
                        <div class="container">
                          <div class="row">  
                            <h6>Invoices</h6>
                            <table class='table '>
                             <thead> 
                                <?php 
                                foreach ($invoiceLoop as $rslt) {
                                    $epoch = $rslt->lines->data[0]->period->start;
                                    $dt = new DateTime("@$epoch");  // convert UNIX timestamp to PHP DateTime
                                    echo "
                                     <tr>
                                      <td>".$rslt->customer_email."</td>
                                      <td>".$rslt->lines->data[0]->description."</td>
                                      <td>".$dt->format('m-d-Y')."</td>
                                      <td class='text-capitalize'>".$rslt->status."</td>
                                      <td><a href='".$rslt->hosted_invoice_url."'>View <span class='glyphicon glyphicon-new-window'></span></a></td>
                                    </tr>
                                    "; }
                                ?>
                                    </thead>
                            </table>
                                
                        </div>
                        </div>                
                      </section>
			    <?php
			   
			    }?>
			        
			 
			    
			    
			 
			  
			  
			  
			    
			    
	
			</div>
		</div>
	</div>
</div>


<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>