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

$keys = $db->query("SELECT * FROM `plg_sub_stripe`")->first(); 

	if(!empty($_POST)){
		$fields = array(
			'stripe_s'=>Input::get('secret'),
			'stripe_p'=>Input::get('public'),
			'stripe_w'=>Input::get('webhook'),
			'stripe_currency'=>Input::get('currency'),
		);
		$db->update('plg_sub_stripe', 1, $fields);
		Redirect::to($us_url_root."users/admin.php?view=plugins_config&plugin=subscriptions&err=Stipe+Options+updated");
	}


?>

<div id="page-wrapper">
	<div class="container">
		<div class="row">
			<div class="col-sm-12">
				hallo sir. (or mam), pls enter secret code here.
				<form class="" action="" method="post">
					<div class="form-group">
						<label for="secret">Stripe Secret</label>
						<input class="form-control" type="text" name="secret"  value="<?=$keys->stripe_s?>" required>
					</div>

					<div class="form-group">
						<label for="public">Stripe Public</label>
						<input class="form-control" type="text" name="public"  value="<?=$keys->stripe_p?>" required>
					</div>
					
					<div class="form-group">
						<label for="webhook">Stripe Webhook</label>
						<input class="form-control" type="text" name="webhook"  value="<?=$keys->stripe_w?>" required>
					</div> 
					
					<div class="form-group">
						<label for="currency">Stripe Currency</label>
						<input class="form-control" type="text" name="currency"  value="<?=$keys->stripe_currency?>" required>
					</div>


					<div class="form-group">
							<input type="submit" name="plugin_cost" value="Update" class="btn btn-primary">
				</form>
					</div>
				</div>
			</div>
		</div>


		<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>
