<?php
if ( !class_exists('QLM_Api_View')){
	class QLM_Api_View
	{

		public $userdata1 = '';
		
		function __construct()
		{
			if (!is_null(WC()->session))
			{
				$this->userdata1 = WC()->session->get( 'is_userdata1' );	
			}
			
		}
		
		/*
			Sends request to API and gets response for RevokeLicenseHttp
			$order_id is the order id
		*/
function send_revoke_request($order_id, $subscription_id)
{
	$end = get_option( 'qlm_end_point');
	$qlm_categories = get_option( 'qlm_categories');
			
    $url_args='?is_vendor=woocommerce';
	$url = $end.'/RevokeLicenseHttp'.$url_args;
	$uid = get_option('qlm_userid');
	$pwd = get_option('qlm_password');

	if (empty($uid) == false)
	{
		$url .= '&is_user='.$uid.'&is_pwd='.$pwd;
	}

	$body = array();
				
	if (!empty($order_id))
	{
		$body['OrderId'] = $order_id;
	}

    if (!empty($subscription_id))
	{
		$body['SubscriptionId'] = $subscription_id;
	}
					
	$args = array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => $body,
				'cookies' => array()
				);
			
	$response = wp_remote_post( $url, $args);
			
	$retarr = array();
			
	if ( is_wp_error( $response ) ) 
	{
		$error_message = $response->get_error_message();
		$retarr['status'] = "error";
		$retarr['err_msg'] = $error_message;
	} 
	else 
	{			
		if(stristr($response['body'], 'Error')){
			$retarr['status'] = "error";
			$retarr['err_msg'] = $response['body'];
			return $retarr;
		}
				
		$license_key = $response['body'];
		$retarr['status'] = "ok";
		$retarr['license_key'] = $license_key;
	}
	return $retarr;
			
}

function send_request($order, $product, $user, $qty, $ymp=0, $exduration=-1, $exdate=null, $is_args="", $subscription, $qlm_activation_key)
{
	if($user == 0)
	{
		$f_name = $order->get_billing_first_name ('view');
		$l_name = $order->get_billing_last_name ('view');
		$customer_email = $order->get_billing_email ('view');
		$customer_company = $order->get_billing_company ('view');
		$customer_address1 = $order->get_billing_address_1 ('view');
		$customer_address2 = $order->get_billing_address_2 ('view');
		$customer_city = $order->get_billing_city ('view');
		$customer_state = $order->get_billing_state('view');
		$customer_country = $order->get_billing_country('view');
		$customer_phone = $order->get_billing_phone ('view');
		$customer_zip = $order->get_billing_postcode ('view');
	}
	else 
	{
		$f_name = get_user_meta( $user, 'billing_first_name', true );
		$l_name = get_user_meta( $user, 'billing_last_name', true );
		$customer_email = get_user_meta( $user, 'billing_email', true );
		$customer_company = get_user_meta( $user, 'billing_company', true );
		$customer_address1 = get_user_meta( $user, 'billing_address_1', true );
		$customer_address2 = get_user_meta( $user, 'billing_address_2', true );
		$customer_city = get_user_meta( $user, 'billing_city', true );
		$customer_state = get_user_meta( $user, 'billing_state', true );
		$customer_country = get_user_meta( $user, 'billing_country', true );
		$customer_phone = get_user_meta( $user, 'billing_phone', true );
		$customer_zip = get_user_meta( $user, 'billing_postcode', true );
	}

	$customer_name = $f_name.' '.$l_name;
			
	$end = get_option( 'qlm_end_point');
	$pid = $product['product_id'];
	$qlm_ver = get_option( 'is_qlmversion');
	$qlm_categories = get_option( 'qlm_categories');

	$is_qlmversion = get_post_meta($pid,'is_qlmversion',true);
	if(empty($is_qlmversion)) $is_qlmversion = $qlm_ver;
			
	$is_productid = get_post_meta($pid,'is_productid',true);
	$major_ver = get_post_meta($pid,'is_majorversion',true);
	$minor_ver = get_post_meta($pid,'is_minorversion',true);
	$is_licensemodel = get_post_meta($pid,'is_licensemodel',true);
	$is_features = get_post_meta($pid,'is_features',true);
	$is_expdate = get_post_meta($pid,'is_expdate',true);
	$is_expduration = get_post_meta($pid,'is_expduration', true);

	$is_additionalactivations = get_post_meta($pid,'is_additionalactivations',true);
	$is_numberofactivationsperkey = get_post_meta($pid,'is_numberofactivationsperkey',true);
	$is_floating = get_post_meta($pid,'is_floating',true);
	$is_maintenanceplan = get_post_meta($pid,'is_maintenanceplan',true);
	$is_usemultipleactivationskey = get_post_meta($pid,'is_usemultipleactivationskey',true);	
	$is_maintduration = get_post_meta($pid,'is_maintduration',true);
	$is_affiliateid = get_post_meta($pid,'is_affiliateid',true);

	
    if(empty($is_expduration)) $is_expduration = $exduration;
	if(empty($is_expdate)) $is_expdate = $exdate;
      
    $url_args='?is_vendor=woocommerce&is_productid='.$is_productid.'&is_majorversion='.$major_ver.'&is_minorversion='.$minor_ver.'&is_qlmversion='.$is_qlmversion;
      
    //.'&is_expduration='.$is_expduration.'&is_expdate='.$is_expdate.'&is_features='.$is_features;
			
    if(empty($is_licensemodel) == false) 
    $url_args = $url_args.'&is_licensemodel='.$is_licensemodel;
        
    if(empty($is_features) == false) 
    $url_args = $url_args.'&is_features='.$is_features;
			
    if(empty($is_expdate) == false) 
    $url_args = $url_args.'&is_expdate='.$is_expdate;
      
    if(empty($is_expduration) == false) 
    $url_args = $url_args.'&is_expduration='.$is_expduration;

	if(empty($is_maintduration) == false) 
    $url_args = $url_args.'&is_maintduration='.$is_maintduration;
        
    if(empty($is_additionalactivations) == false) 
    $url_args = $url_args.'&is_additionalactivations='.$is_additionalactivations;

	if(empty($is_usemultipleactivationskey) == false) 
    $url_args = $url_args.'&is_usemultipleactivationskey='.$is_usemultipleactivationskey;		
        
    if(empty($is_numberofactivationsperkey) == false) 
    $url_args = $url_args.'&is_numberofactivationsperkey='.$is_numberofactivationsperkey;
        
    if(empty($is_floating) == false) 
    $url_args = $url_args.'&is_floating='.$is_floating;

	if(empty($is_affiliateid) == false) 
    $url_args = $url_args.'&is_affiliateid='.$is_affiliateid;

	if(empty($is_args) == false) 
    $url_args = $url_args.'&is_args='.$is_args;

	if(empty($qlm_activation_key) == false) 
    $url_args = $url_args.'&is_avkey='.$qlm_activation_key;

	if(empty($this->userdata1) == false) 
    $url_args = $url_args.'&is_userdata1='.$this->userdata1;

	if(empty($qlm_activation_key) == false) 
	{
		$url = $end.'/UpgradeLicense'.$url_args;
	}
	else if(empty($is_maintenanceplan) == false) 
	{
		$url = $end.'/RenewMaintenancePlan'.$url_args;
	}	
	else
	{
		$url = $end.'/GetActivationKeyWithExpiryDate'.$url_args;
    }
      
	$uid = get_option( 'qlm_userid');
	$pwd = get_option( 'qlm_password');
	if (empty($uid) == false)
	{
		$url .= '&is_user='.$uid.'&is_pwd='.$pwd;
	}
	$body = array(
				'OrderId' => $order->id,						
				'Customer_Name' => $customer_name,
				'Customer_Email' => $customer_email,
				'Customer_Company' => $customer_company,
				'Customer_Address1' => $customer_address1,
				'Customer_Address2' => $customer_address2,
				'Customer_City' => $customer_city,
				'Customer_State' => $customer_state,
				'Customer_Zip' => $customer_zip,
				'Customer_Country' => $customer_country,
				'Customer_Phone' => $customer_phone,
				'is_quantity' => $qty,
				'yearly_maintenance_plan' => $ymp						
				);

	if (!empty($subscription))
	{
		$body['SubscriptionId'] = $subscription->id;
	}

	$args = array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => $body,
				'cookies' => array()
				);
			
	$response = wp_remote_post( $url, $args);
			
	$retarr = array();
			
	if ( is_wp_error( $response ) ) {
		$error_message = $response->get_error_message();
		$retarr['status'] = "error";
		$retarr['err_msg'] = $error_message;
			   
		//send to order notes with dt
	} else {
				
		if(stristr($response['body'], 'Error')){
			$retarr['status'] = "error";
			$retarr['err_msg'] = $response['body'];
			return $retarr;
		}
				
		$license_key = $response['body'];
		$retarr['status'] = "ok";
		$retarr['license_key'] = $license_key;
	}
	return $retarr;
			
}
		
		/*
			Sends request to API and gets response for RenewSubscriptionHttp
			$user  is the userid of current customer
			$qty  quantity of product
			$order_id   is order id
			$ymp	yearly mainteance plan. it is optional
			$exduration  Duration of the expiary.
			$exdate Expiry date
			$activation_key  is the activation key that we already have for this item
		*/		
		function send_recurring_request($order, $product, $user, $qty, $exduration, $exdate, $ymp=0, $subscription){
			
			$f_name = get_user_meta( $user, 'billing_first_name', true );
			$l_name = get_user_meta( $user, 'billing_last_name', true );
			$customer_email = get_user_meta( $user, 'billing_email', true );
			$customer_company = get_user_meta( $user, 'billing_company', true );
			$customer_address1 = get_user_meta( $user, 'billing_address_1', true );
			$customer_address2 = get_user_meta( $user, 'billing_address_2', true );
			$customer_city = get_user_meta( $user, 'billing_city', true );
			$customer_state = get_user_meta( $user, 'billing_state', true );
			$customer_country = get_user_meta( $user, 'billing_country', true );
			$customer_phone = get_user_meta( $user, 'billing_phone', true );
			$customer_zip = get_user_meta( $user, 'billing_postcode', true );
			$customer_name = $f_name.' '.$l_name;
			
			$end = get_option( 'qlm_end_point');
							
			$qlm_ver = get_option( 'is_qlmversion');


			$url = $end.'/RenewSubscriptionHttp?is_vendor=woocommerce';
			
			$uid = get_option( 'qlm_userid');
			$pwd = get_option( 'qlm_password');
			if (empty($uid) == false)
			{
				$url .= '&is_user='.$uid.'&is_pwd='.$pwd;
			}
			
			//$pid = $product['product_id'];
			//$is_qlmversion = get_post_meta($pid,'is_qlmversion',true);

			//if(empty($is_qlmversion)) $is_qlmversion = $qlm_ver;
			//if (empty($is_qlmversion) == false)
			//{
				//$url .= '&is_qlmversion='.$is_qlmversion;
			//}
      
			$body = array(
						'OrderId' => $order->id,
						'SubscriptionId' => $subscription->id, 
						'Customer_Name' => $customer_name,
						'Customer_Email' => $customer_email,
						'Customer_Company' => $customer_company,
						'Customer_Address1' => $customer_address1,
						'Customer_Address2' => $customer_address2,
						'Customer_City' => $customer_city,
						'Customer_State' => $customer_state,
						'Customer_Zip' => $customer_zip,
						'Customer_Country' => $customer_country,
						'Customer_Phone' => $customer_phone,
						'is_quantity' => $qty,
						'yearly_maintenance_plan' => $ymp						
						);
            
            if ($exduration > 0)
            {
              $body['is_expduration']  = $exduration ;
            }
            
            if ($exdate > 0)
            {
              $body['is_expdate']  = $exdate ;
            }
					
			$args = array(
						'method' => 'POST',
						'timeout' => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => $body,
						'cookies' => array()
						);
			
			$response = wp_remote_post( $url, $args);
			
			$retarr = array();
			
			if ( is_wp_error( $response ) ) {
			   $error_message = $response->get_error_message();
			   $retarr['status'] = "error";
			   $retarr['err_msg'] = $error_message;
			   
			   //send to order notes with dt
			} else {
				
				if(stristr($response['body'], 'Error')){
					$retarr['status'] = "error";
					$retarr['err_msg'] = $response['body'];
					return $retarr;
				}
				
				$license_key = $response['body'];
			    $retarr['status'] = "ok";
			    $retarr['license_key'] = $license_key;
			}
			return $retarr;
			
		}
		
		
		//It is settings form
		function show_setting(){
			$end = get_option( 'qlm_end_point');
			$ver = get_option( 'is_qlmversion');
			$licenseKeyFieldName = get_option( 'qlm_licenseKeyFieldName');
			$qlm_categories = get_option( 'qlm_categories');
			$qlm_user_role = get_option( 'qlm_user_role');
			$qlm_maintenance_plan_identifier = get_option( 'qlm_maintenance_plan_identifier');
			$qlm_webhook_secret_key = get_option( 'qlm_webhook_secret_key');			
			$qlm_product_addon = get_option( 'qlm_product_addon');

			if (is_plugin_active('woo-custom-product-addons/start.php') == false) 
			{
				debug_message (610, $order, __FUNCTION__ , 'WooCommerce Custom Product Add-on is not installed .');
				$qlm_product_addon = false;
				update_option( 'qlm_product_addon', $qlm_product_addon );				
			} 

			$qlm_revoke_when_order_cancelled = get_option( 'qlm_revoke_when_order_cancelled');
			$qlm_revoke_when_subscription_cancelled = get_option( 'qlm_revoke_when_subscription_cancelled');
			$qlm_next_payment_date_based_on_schedule = get_option( 'qlm_next_payment_date_based_on_schedule');
			$qlm_process_order_on_status_completed = get_option( 'qlm_process_order_on_status_completed');

			$qlm_send_mail = get_option( 'qlm_send_mail');
			$qlm_enable_log = get_option( 'qlm_enable_log');
			
			
			if(empty($licenseKeyFieldName)) $licenseKeyFieldName = 'licenseKey';

			$uid = get_option( 'qlm_userid');
			$pwd = get_option( 'qlm_password');
			?>
			<div class="tw-bs container" style="max-width:835px">
				<div class="row">
					<div class="col-md-12"><h2 style="text-align:center;">QLM Settings</h2></div>
				</div>
				<div style="background-color:white;">
					<form method="post" action="">
						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>License Server URL</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input class="form-control" type="text" value="<?php if($end){ echo $end; } ?>" required name="qlm_end_point"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>Example: https://qlm3.net/qlmdemo/qlmLicenseServer/qlmservice.asmx</i></div>							
						</div>
						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Engine Version</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input class="form-control" type="text" value="<?php if($ver){ echo $ver; } ?>" required name="is_qlmversion"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>The engine version is the version of the license engine. The possible values are: 5.0.00 or 6.0.00.</i></div>
						</div>
						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>License Caption</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input class="form-control" type="text" value="<?php if($licenseKeyFieldName){ echo $licenseKeyFieldName; } ?>" name="qlm_licenseKeyFieldName"></div>
						</div>			
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>Caption to use in the Woocommerce order when displaying the Activation Key.</i></div>							
						</div>
						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>User</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input class="form-control" type="text" value="<?php if($uid){ echo $uid; } ?>" name="qlm_user_id"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>The user/password settings must match the values specified in the QLM Management Console / Manage Keys / 3rd Party Extensions / WooCommerce. </i></div>							
						</div>
						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Password</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input class="form-control" type="password" value="<?php if($pwd){ echo $pwd; } ?>" name="qlm_password"></div>
						</div>
												
						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Categories</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input class="form-control" type="text" value="<?php if($qlm_categories){ echo $qlm_categories; } ?>" name="qlm_categories"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>Specify the categories of the products that QLM should process. You can enter a comma separated list of categories to process. If no value is set, all categories are processed.</i></div>							
						</div>
						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>User Role</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input class="form-control" type="text" value="<?php if($qlm_user_role){ echo $qlm_user_role; } ?>" name="qlm_user_role"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>Specify the user role that should be assigned to the user after purchase.</i></div>
						</div>
						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Maintenance Plan Identifier</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input class="form-control" type="text" value="<?php if($qlm_maintenance_plan_identifier){ echo $qlm_maintenance_plan_identifier; } ?>" name="qlm_maintenance_plan_identifier"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>If you have created a Maintenance Plan attribute to allow customers to opt-in/out of purchasing a maintenance plan, specify the label that you used for the maintenance plan attribute. QLM uses this identifier to associate this attribute to the QLM Maintenance Plan feature.</i></div>
						</div>
						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Webhook Secret Key</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input class="form-control" type="password" value="<?php if($qlm_webhook_secret_key){ echo $qlm_webhook_secret_key; } ?>" name="qlm_webhook_secret_key"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>Specify the secret key used to authenticate the webhook request. </i></div>
						</div>
						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Enable Upgrade</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input type="checkbox" value="1" <?php checked( '1', get_option( 'qlm_product_addon' ) ); ?> name="qlm_product_addon" id="qlm_product_addon"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>Enable "WooCommerce Custom Product Addons" to support product ugprades.</i></div>
						</div>

						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Revoke when order cancelled</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input type="checkbox" value="1" <?php checked( '1', get_option( 'qlm_revoke_when_order_cancelled' ) ); ?> name="qlm_revoke_when_order_cancelled" id="qlm_revoke_when_order_cancelled"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>Revoke all licenses associated with an order when it is cancelled.</i></div>
						</div>

						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Revoke when subscription cancelled</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input type="checkbox" value="1" <?php checked( '1', get_option( 'qlm_revoke_when_subscription_cancelled' ) ); ?> name="qlm_revoke_when_subscription_cancelled" id="qlm_revoke_when_subscription_cancelled"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>Revoke all licenses associated with a subscription when it is cancelled.</i></div>
						</div>

						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Next payment date based on schedule</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input type="checkbox" value="1" <?php checked( '1', get_option( 'qlm_next_payment_date_based_on_schedule' ) ); ?> name="qlm_next_payment_date_based_on_schedule" id="qlm_next_payment_date_based_on_schedule"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>By default, WooCommerce calculates the next payment date from the time of the last payment. This option calculates the next payment date from the scheduled payment date. This is required if you enabled WooCommerce Subscriptions Early Renewal.</i></div>
						</div>

						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Process request when order status is completed (default is unchecked).</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input type="checkbox" value="1" <?php checked( '1', get_option( 'qlm_process_order_on_status_completed' ) ); ?> name="qlm_process_order_on_status_completed" id="qlm_process_order_on_status_completed"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>By default, QLM processes an order when the order status is "in progress" but not completed. This allows QLM to insert the generated license keys in the order. In some special cases, you may want to have QLM process the request only after the order status changes to completed. Beware that, if you enable this option, license keys will not be stored in the order.</i></div>
						</div>

						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Send Email</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input type="checkbox" value="1" <?php checked( '1', get_option( 'qlm_send_mail' ) ); ?> name="qlm_send_mail" id="qlm_send_mail"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>Enable the QLM plugin to send an email after an order is processed. If you enable this option, you must also add the WP custom field is_send_mail to your product definition.</i></div>
						</div>

						<div class="row">
							<div class="col-md-4" style="margin-top:1em;"><b>Enable Logging</b></div>
							<div class="col-md-8" style="margin-top:1em;"><input type="checkbox" value="1" <?php checked( '1', get_option( 'qlm_enable_log' ) ); ?> name="qlm_enable_log" id="qlm_enable_log"></div>
						</div>
						<div class="row">
						    <div class="col-md-4" style="margin-top:0em;"></div>
							<div class="col-md-8" style="margin-top:0em;"><i>Enable the QLM plugin to log debug messages to a dedicated log file. The log file can be accessed from WooCommerce / Status / Logs.</i></div>
						</div>


						<div class="row">
							<div class="col-md-12" style="margin-top:1em;">
								<input type="submit" value="Save Setting" name="qlm_submit_set" class="btn btn-primary">
							</div>
						</div>
					</form>
				</div>
			</div>
			<?php 
		}
		
	}// calss ends
}//if ends