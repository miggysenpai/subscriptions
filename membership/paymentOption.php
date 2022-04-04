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
if(!hasPerm([2],$user->data()->id) || !(in_array($user->data()->id,$master_account))){die("He' dead, Jim");}
$edit = Input::get('edit');
$planQ = $db->query("SELECT * FROM plg_mem_cost WHERE id = ?",[$edit]);
$planC = $planQ->count();
if($planC < 1){
	Redirect::to($us_url_root."users/admin.php?view=plugins_config&plugin=membership&err=Not+found");
}
$plan = $planQ->first();

//stripe connect stuff
require_once($abs_us_root.$us_url_root.'usersc/plugins/membership/vendor/autoload.php');
$keys = $db->query("SELECT * FROM `plg_mem_stripe`")->first(); 
$stripe = new \Stripe\StripeClient($keys->stripe_s);
 

	if(!empty($_POST['days'])){
		$fields = array(
			'cost'=>Input::get('cost'),
			'days'=>Input::get('days'),
			'stripe_priceID'=>Input::get('price'),
			'descrip'=>Input::get('descrip'),
			'disabled'=>Input::get('disabled'),
		);
		$db->update('plg_mem_cost',$edit,$fields);
		Redirect::to($us_url_root."users/admin.php?view=plugins_config&plugin=membership&err=Pricing+option+updated");
	}
	
    if(Input::get('delete') == "delete"){
        $edit = Input::get('edit');
        $db->deleteById('plg_mem_cost', $edit);
        $stripe->prices->update($plan->stripe_priceID, ['active' => 'false']);
        $stripe_product = $stripe->prices->retrieve($plan->stripe_priceID,[]);
        $stripe->products->update($stripe_product->product,['active' => 'false']);
        Redirect::to($us_url_root."users/admin.php?view=plugins_config&plugin=membership&err=Poduct+deleted");
    }
    
    if(Input::get('delete') == "warning"){?>
    <div class="jumbotron">
      <h1 class="display-4">BIG WARNINGGGGG!</h1>
      <p class="lead">Deleting will only happen in YOUR  database. It will get archived in stripe and no more memberships will be able to be made with this product.</p>
                    <br />
                    In order to completely delete this product from stripe, please do the following.
                    <br/>
                    Go to stripe, products, then archived(if you delete here.), then you can manually delete it there. 
                    <br />
                    Stripe does NOT support deleting via API at this time. 
      <hr class="my-4">
      <p>are ya sure???. you can disable the product by choosing the disable option.</p>
      <p class="lead">
        <a class="btn btn-danger btn-lg" href="?delete=delete&edit=<?=Input::get('edit')?>" role="button">Delete</a>
      </p>
    </div>
    
    
        
    <?}
?>


<div id="page-wrapper">
	<div class="container">
		<div class="row">
			<div class="col-sm-12">
				Please note. Do not delete pricing options because it could break things. Use the disable feature.  New prices will be used when people renew.
				<form class="" action="" method="post">
					<div class="form-group">
						<label for="days"># of Days</label>
						<input class="form-control" type="number" name="days" value="<?=$plan->days?>" min="1" step="1" placeholder="30" required>
					</div>

					<div class="form-group">
						<label for="cost">Cost - No Symbols <i>Must be updated in stripe dashboard aswell. Stripe API sucks </i></label>
						<input class="form-control" type="number" name="cost" value="<?=$plan->cost?>" min=".00" step=".01" placeholder="30.00" required>
					</div>
					
					<div class="form-group">
						<label for="price">Stripe Price <i>Do not edit, this is only for reference</i></label>
						<input class="form-control" type="text" name="price" value="<?=$plan->stripe_priceID?>"  placeholder="price_xxx" required disabled>
					</div>

					<div class="form-group">
						<label  for="descrip">Description - We will automatically add the number of days to this description</label>
						<input class="form-control" type="text" name="descrip" value="<?=$plan->descrip?>" placeholder="1 month" required>
					</div>

					<div class="form-group">
						<label  for="disabled">Disable Option?</label>
						<select class="form-control" name="disabled" required>
							<option value="" disabled selected>--Choose--</option>
							<option value="0">Enabled</option>
							<option value="1">Disabled</option>

						</select>
					</div>

					<div class="form-group">
							<input type="submit" name="plugin_cost" value="Update" class="btn btn-primary">
							<a href="?edit=<?=$plan->id?>&delete=warning" name="" value="delete" class="btn btn-danger">Delete</a>
				</form>
					</div>
				</div>
			</div>
		</div>


		<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>
