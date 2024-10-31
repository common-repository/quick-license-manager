<?php
if ( !class_exists('QLM_Api_View')){
	class QLM_Api_View{
		
		function __construct(){
			
			
		}
		
		/*
			Sends request to API and gets response for GetActivationKeyWithExpiryDate
			$product  is the product object for which we need license_key
			$user  is the userid of current customer
			$qty  quantity of product
			$order_id   is order id
			$yearly  yearly mainteance plan. it is optional
			$exduration  Duration of the expiary. It is optional
		*/
		function send_request($product, $user, $qty,$order_id, $yearly=0, $exduration=false){
			
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
			$pid = $product['product_id'];
			$qml_ver = get_option( 'is_qlmversion');
			
			$is_productid = get_post_meta($pid,'is_productid',true);
			$major_ver = get_post_meta($pid,'is_majorversion',true);
			$minor_ver = get_post_meta($pid,'is_minorversion',true);
			$is_features = get_post_meta($pid,'is_features',true);
			$is_expdate = get_post_meta($pid,'is_expdate',true);
			$is_expduration = get_post_meta($pid,'is_expduration',true);
      
      if (empty($is_expduration) == true)
      {      
        if($exduration) $is_expduration = "&is_expduration=$exduration";
      }
      
			$url = $end.'/GetActivationKeyWithExpiryDate?is_vendor=woocommerce&is_productid='.$is_productid.'&is_majorversion='.$major_ver.'&is_minorversion='.$minor_ver.'&is_qlmversion='.$qml_ver.'&is_expduration='.$is_expduration.'&is_expdate='.$is_expdate.'&is_features='.$is_features;
      
      $uid = get_option( 'qlm_userid');
			$pwd = get_option( 'qlm_password');
      if (empty($uid) == false)
      {
        $url .= '&is_user='.$uid.'&is_pwd='.$pwd;
      }
			
			$body = array(
						'OrderId' => $order_id,
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
						'yearly_maintenance_plan' => $yearly
						);
						
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
			$yearly  yearly mainteance plan. it is optional
			$exduration  Duration of the expiary.
			$exdate Expiary date
			$activation_key  is the activation key that we already have for this item
		*/
		function send_recurring_request($user, $qty, $order_id, $exduration, $exdate, $activation_key, $yearly=0){
			
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
			
			$qml_ver = get_option( 'is_qlmversion');
			
			
			$url = $end.'/RenewSubscriptionHttp?is_vendor=woocommerce';
      
      $uid = get_option( 'qlm_userid');
			$pwd = get_option( 'qlm_password');
      if (empty($uid) == false)
      {
        $url .= '&is_user='.$uid.'&is_pwd='.$pwd;
      }
			
			$body = array(
						'OrderId' => $order_id,
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
						'yearly_maintenance_plan' => $yearly,
						'is_expduration' => $exduration,
						'is_expdate' => $exdate,
						'is_avkey' => $activation_key
						
						);
					
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
			$uid = get_option( 'qlm_userid');
			$pwd = get_option( 'qlm_password');
			?>
			<div class="tw-bs container" style="max-width:835px">
				<div class="row">
					<div class="col-md-12"><h2 style="text-align:center;">Settings for API</h2></div>
				</div>
				<div style="background-color:white;">
					<form method="post" action="">
						<div class="row">
							<div class="col-md-2" style="margin-top:1em;"><b>End Point</b></div>
							<div class="col-md-10" style="margin-top:1em;"><input class="form-control" type="text" value="<?php if($end){ echo $end; } ?>" required name="qlm_end_point"></div>
						</div>
						<div class="row">
							<div class="col-md-2" style="margin-top:1em;"><b>QLM Version</b></div>
							<div class="col-md-10" style="margin-top:1em;"><input class="form-control" type="text" value="<?php if($ver){ echo $ver; } ?>" required name="is_qlmversion"></div>
						</div>
						<div class="row">
							<div class="col-md-2" style="margin-top:1em;"><b>User</b></div>
							<div class="col-md-10" style="margin-top:1em;"><input class="form-control" type="text" value="<?php if($uid){ echo $uid; } ?>" name="qlm_user_id"></div>
						</div>
						<div class="row">
							<div class="col-md-2" style="margin-top:1em;"><b>Password</b></div>
							<div class="col-md-10" style="margin-top:1em;"><input class="form-control" type="text" value="<?php if($pwd){ echo $pwd; } ?>" name="qlm_password"></div>
						</div>
						<div class="row">
							<div class="col-md-12" style="margin-top:1em;">
								<b>Note: </b>The user/password settings must match the values specified in the QLM Management Console / Manage Keys / Commerce Providers / WooCommerce.
							</div>
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