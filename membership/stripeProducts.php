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
$keys = $db->query("SELECT * FROM `plg_mem_stripe`")->first(); 
require_once($abs_us_root.$us_url_root.'usersc/plugins/membership/vendor/autoload.php');
$stripe = new \Stripe\StripeClient($keys->stripe_s);
$price_stripe = $stripe->prices->all([]);
?>

<div id="page-wrapper">
	<div class="container">
		<div class="row">
		    <div class="col-sm-12">
		        <button type="button" onclick="window.location.href = '<?=$us_url_root?>users/admin.php?view=plugins_config&plugin=membership';" 
                name="button" class="btn btn-primary">Configure Membership</button>
		        
		    </div>
			<div class="col-sm-12">
			    <?
			  //Remove to view JSON LIST ARRAY   
			  // echo $price_stripe;
			    ?>
			    <table class="table table-borderless">
			     <thead>
                    <tr>
                      <th scope="col">id</th>
                      <th scope="col">Description</th>
                    </tr>
                  </thead>   
                <tbody>
                 <?
                foreach ($price_stripe as $prc) {
                    $priceDecimal = $prc->unit_amount/pow(10,2);
                        echo "
                        
                         <tr>
                          <td class=''>".$prc->id." </td>
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
			</div>
		</div>
	</div>
</div>


<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>