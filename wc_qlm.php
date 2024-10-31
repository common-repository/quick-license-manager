<?php

/**
 * Plugin Name: WooCommerce Quick License Manager Integration
 * Plugin URI: https://wordpress.org/plugins/quick-license-manager/
 * Description: Automates the creation of license keys when orders are placed with WooCommerce
 * Version: 2.4.15
 * Author: Soraco Technologies Inc.
 * Author URI: https://soraco.co
 * WC requires at least: 4.0.0
 * WC tested up to: 8.1.1
 */

define("QLM_DEBUG", false);
define("QLM_ENABLE_SUBSCRIPTION", true);


require_once(__DIR__ ."/classes/qlm_api.php");
require_once(__DIR__ ."/classes/qlm_api_view.php");
require_once(__DIR__ ."/classes/qlm_emails.php");

//
//Debug Message Next Index: 615
// 

function debug_message($event_id, $order, $function_name, $message)
{

	// if (QLM_DEBUG && ($order != ''))
	$qlm_enable_log = get_option( 'qlm_enable_log');

	if (($qlm_enable_log == true) && ($order != ''))	
	{
		//$order->add_order_note($message);
		 qlm_wordpress_log ($event_id, $order, $function_name, $message) ;
	}
}

function qlm_wordpress_log ($event_id, $order, $function_name, $str) 
{
	$uploads  = wp_upload_dir( null, false );
	$logs_dir = $uploads['basedir'] . '/wc-logs';

	if ( ! is_dir( $logs_dir ) ) {
		mkdir( $logs_dir, 0755, true );
	}

	$logfile = $logs_dir . '/' . 'qlm-'.$order->id.'.log';		

	$d = date("j-M-Y H:i:s e");
	error_log("[$d] [$event_id] [OrderID: $order->id] [$function_name] $str\r\n", 3, $logfile);
}

/*
function get_product_addon_value($order, $field)
{
	foreach ($order->get_items() as $item_id => $item) 
	{
        $meta_data = $item->get_meta(WCPA_ORDER_META_KEY);
        // $meta_data this $meta_data will have all the user submited data.
		// You can iterate through this and get the required value		
        $fieldValue= '';

		if (is_array($meta_data) || is_object($meta_data))
		{
			foreach ($meta_data as $v)
			{
				if($v['name']== $field)
				{
					$fieldValue = $v['value'];
					debug_message (5, $order, $field.': '.$fieldValue);
					return $fieldValue;
				}
			}     
		}
	}	

	return "";
}
*/

// Get the value of metadata associated to the product item being Order
// This is used in the context of a product upgrade where the customer must enter a previous Activation Key
// This feature requires a plugin called "WooCommerce Custom Product Addons"
function get_item_addon_value($order, $item, $field)
{	
	$qlm_product_addon = get_option( 'qlm_product_addon');

    if ($qlm_product_addon == false) 
	{
		debug_message (9, $order, __FUNCTION__ , 'WooCommerce Custom Product Add-on is not enabled.');
		return;
	}

	if (is_plugin_active('woo-custom-product-addons/start.php') == false) 
	{
		debug_message (610, $order, __FUNCTION__ , 'WooCommerce Custom Product Add-on is not installed .');
		return;
	} 
	

    $meta_data = $item->get_meta(WCPA_ORDER_META_KEY);

	if (is_null($meta_data))
	{
		debug_message (12, $order, __FUNCTION__ , ':metadata is null .');
		return "";
	}



    // $meta_data this $meta_data will have all the user submited data.
	// You can iterate through this and get the required value		
    $fieldValue= '';

	if (is_array($meta_data) || is_object($meta_data))
	{
		foreach ($meta_data as $v)
		{
			if($v['name']== $field)
			{
				$fieldValue = $v['value'];
				debug_message (10, $order, __FUNCTION__ , $field.': '.$fieldValue);
				return $fieldValue;
			}
			else 
			{
				debug_message (11, $order, __FUNCTION__ , $v['name'].': '.$v['value']);
			}
		}     
	}
    

	return "";
}

function change_role_on_purchase( $order ) 
{						
	$qlm_user_role = get_option( 'qlm_user_role');

	debug_message (15, $order, __FUNCTION__ , 'Start setting user role to: '.$qlm_user_role);
	
	if (is_null($qlm_user_role) || ($qlm_user_role == '')) return;
	
	debug_message (20, $order, __FUNCTION__ , 'setting user role to: '.$qlm_user_role);

	//$user = get_userdata($order->user_id);
	$user  = new WP_User($order->user_id);
	
	if (!is_null($user))
	{
		debug_message (25, $order, __FUNCTION__ , 'adding user role: '.$qlm_user_role);
		//$user->remove_role( 'Customer' );
		//$user->set_role( $qlm_user_role );
		$user->add_role( $qlm_user_role );
		debug_message (30, $order, __FUNCTION__ , 'added user role: '.$qlm_user_role);
	}
}

function qlm_get_order_meta($order_id, $key, $single = true)
{
	return get_post_meta ($order_id, $key, $single);
}

function qlm_update_order_meta($order_id, $meta_key, $meta_value)
{		
		update_post_meta($order_id, $meta_key, $meta_value);
}


class QlmWebhookSetup{
     
    public function __construct(){
        require_once 'utils/woo-subs-helper-functions.php';
        add_action('rest_api_init',array($this,'create_custom_endpoint_to_listen'));
        //add_action('admin_menu',array($this,'admin_options_for_API_Endpoint'));
    }

    function create_custom_endpoint_to_listen(){
        require_once 'classes/qlm_webhook.php';
        (new QlmWebhook())->init_route();
    }
 }


if ( !class_exists('QLM_Main')){
	class QLM_Main{
		
		function __construct()
		{
			new QlmWebhookSetup();

			register_activation_hook( __FILE__, array(&$this, 'install') );

			add_action('admin_menu', array(&$this,'admin_menu'));
			add_action('wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts') );
			add_action('admin_enqueue_scripts', array(&$this, 'wp_enqueue_scripts') );
			add_action('woocommerce_order_status_processing',array(&$this, 'order_status_changed'));
			add_action('woocommerce_order_status_completed',array(&$this, 'order_status_completed'));
			
			add_action('woocommerce_add_to_cart',array(&$this, 'action_add_to_cart'), 10, 6);
			add_action('add_meta_boxes', array(&$this,'add_meta') );
			
			//add_action('woocommerce_payment_complete',array(&$this, 'so_payment_complete'));    
			add_action('woocommerce_order_status_cancelled',array(&$this, 'action_woocommerce_cancelled_order'));
			add_action('woocommerce_subscription_status_cancelled',array(&$this, 'action_woocommerce_cancelled_subscription'));


			// retrieve the early renewal setting in WC
			//$er = get_option( 'woocommerce_subscriptions_enable_early_renewal' );
			

			$qlm_next_payment_date_based_on_schedule = get_option( 'qlm_next_payment_date_based_on_schedule');
			if ($qlm_next_payment_date_based_on_schedule == true)
			{
				//By default, WooCommerce Subscriptions will calculate the next payment date for a subscription from the time of the last payment. 
				// This filter changes that to preserve the original schedule and calculate the next payment date from the scheduled payment date, 
				// not the time the payment was actually processed.				
				add_filter( 'wcs_calculate_next_payment_from_last_payment', '__return_false' );				
			}
			
		}

		

		/**
		 * Function for `woocommerce_add_to_cart` action-hook.
		 * 
		 * @param string  $cart_id          ID of the item in the cart.
		 * @param integer $product_id       ID of the product added to the cart.
		 * @param integer $request_quantity Quantity of the item added to the cart.
		 * @param integer $variation_id     Variation ID of the product added to the cart.
		 * @param array   $variation        Array of variation data.
		 * @param array   $cart_item_data   Array of other cart item data.
		 *
		 * @return void
		 */		
		function action_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
		{
			if (!is_null(WC()->session))
			{				
				if (isset ($_GET['is_userdata1']))
				{
					$userdata1 = $_GET['is_userdata1'];
					if (!is_null($userdata1))
					{
						WC()->session->set('is_userdata1', $userdata1);		
					}
				}
			}
			
		}
	
		
		function so_payment_complete( $order_id )
		{
			$order = wc_get_order( $order_id );

			debug_message(35, $order, __FUNCTION__ , 'Payment completed. Setting Order Status to Completed.');

			//change_role_on_purchase($order);
			
			if ( order_contains_renewal( $order ) )
            {
                  debug_message(40, $order, __FUNCTION__ , 'Detected renewal order id:'.$order_id);
                  //$parent_order_id = WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id->id );

                  $subscriptions = wcs_get_subscriptions_for_renewal_order( $order_id );       
				  
				  foreach ( $subscriptions as $subscription )
                  {
                    debug_message(45, $order, __FUNCTION__ , 'subscription id:'.$subscription->id);

                    //$parent_order_id = WC_Subscriptions_Renewal_Order::get_parent_order_id( $subscription->id );
                    $orig_subscription = $subscription;
                    $orig_order_id = $subscription->order->id;
					// we only support one subscription
                    break;
				}

				if(!empty($orig_subscription))
				{
					$orig_subscription->update_status('active');
				}
			}
			else 
			{
				debug_message(50, $order, __FUNCTION__ , 'Payment completed. This is not a renewal');										
			}

			//debug_message(55, $order, 'QLM -so_payment_complete - Set Order Status to Completed.');										
			//$order->update_status('completed');
		}

		function action_woocommerce_cancelled_order( $order_id ) 
		{ 
			$qlm_revoke_when_order_cancelled = get_option( 'qlm_revoke_when_order_cancelled');

			if ($qlm_revoke_when_order_cancelled == false) return;

			$order = new WC_Order($order_id);

			$qlm_v = new QLM_Api_View();
			
			debug_message(59, $order, __FUNCTION__, 'Cancelling order id:'.$order_id);

			$res = $qlm_v->send_revoke_request($order_id, '');

			//check return status and handle error
			if($res['status'] == 'error')
			{
				$msg = $date_time.' '.$res['err_msg'];
				debug_message(60, $order, __FUNCTION__, $msg);
			}
			else
			{
				$license_key = $res['license_key'];

				//$old_meta = get_post_meta($order_id, '_qlm_license_keys', true);
				$old_meta = qlm_get_order_meta ($order_id, '_qlm_license_keys', true);

				$update_meta = $old_meta.'<br/>'.$license_key.' Date: '.date("Y/m/d h:i:s");
				
				qlm_update_order_meta ($order_id, '_qlm_license_keys', $update_meta);
			}
		}

		function action_woocommerce_cancelled_subscription( $subscription ) 
		{ 
			$qlm_revoke_when_subscription_cancelled = get_option( 'qlm_revoke_when_subscription_cancelled');

			if ($qlm_revoke_when_subscription_cancelled == false) return;

			$order = $subscription->order;

			$qlm_v = new QLM_Api_View();
			
			debug_message(65, $order, __FUNCTION__, 'Cancelling subscription id:'.$subscription->id);

			$res = $qlm_v->send_revoke_request('', $subscription->id);

			//check return status and handle error
			if($res['status'] == 'error')
			{
				$msg = $date_time.' '.$res['err_msg'];
				debug_message(70, $order,  __FUNCTION__, $msg);
			}
			else
			{
				$license_key = $res['license_key'];

				$old_meta = qlm_get_order_meta ($order->id, '_qlm_license_keys', true);

				$update_meta = $old_meta.'<br/>'.$license_key.' Date: '.date("Y/m/d h:i:s");

				qlm_update_order_meta($order->id, '_qlm_license_keys', $update_meta);
			}
		}
		
		//sends email when an order is marked as completed
		function mail_on_order_completed($order)
		{
			global $woocommerce;

			$qlm_send_mail  = get_option( 'qlm_send_mail');

			if ($qlm_send_mail == false) 
			{
				debug_message(74, $order,  __FUNCTION__, 'mail_on_order_completed skipped because the option to send mail is disabled.');
				return;
			}

			debug_message(75, $order,  __FUNCTION__, 'mail_on_order_completed triggered');
			
			//change_role_on_purchase( $order);
	        

			$items = $order->get_items();
			

			foreach($items as $item)
            {
				$pid = $item['product_id'];
				$option_send_mail = get_post_meta($pid,'is_send_mail', true);

				if (($option_send_mail == 'no') || ($option_send_mail == 'false'))
				{
					break;
				}

			}

			//$option_send_mail = get_option('is_send_mail');
			if (($option_send_mail == 'no') || ($option_send_mail == 'false'))
			{
	            debug_message(80, $order,  __FUNCTION__, 'skipping send mail, value:'.$option_send_mail);
				return;
			}
			else
			{
	            debug_message(85, $order,  __FUNCTION__, 'starting send mail, value:'.$option_send_mail);
			}

			
	        debug_message(90, $order,  __FUNCTION__, 'mail_on_order_completed processing');
			
			$qlm_mail=new QLM_Emails();
			
			

			$user_id = $order->user_id;
			$user = get_userdata($user_id);
			
			//$user_name = $user->display_name;
			//$user_email = $user->user_email;

			$user_name = $order->billing_last_name.' '.$order->billing_first_name;
			$user_email = $order->billing_email;

            debug_message(95, $order,  __FUNCTION__, 'User ID1:'.$user_id.'-User Name:'.$user_name.'-User Email:'.$user_email);
			
			$default_header = $qlm_mail->show_default_header();
			$email_header = get_option( 'qlm_header', $default_header );
			$email_header = str_replace( '#customername#' ,$user_name, $email_header );

			$email_subject= get_option( 'qlm_subject', 'Order Confirmation' );
			$email_subject = str_replace( '#order_id#' , $order->id, $email_subject );

			
			$template = "";
			foreach ($items as $item)
            {
				if ($this->is_qlm_item ($order, $item) == false) 
				{
					continue;
				}

				$product_id = $item['product_id'];					

				if($item['variation_id'] != 0 )
				{
					$licenseKey = qlm_get_order_meta ( $order->id, '_qlm_license_key_'.$item['variation_id'], true );	
				}
				else
				{
					$licenseKey = qlm_get_order_meta ( $order->id, '_qlm_license_key_'.$product_id, true );	
				}

                debug_message(100, $order,  __FUNCTION__, 'Processing email for license key1:'.$licenseKey);

				$email_template = $qlm_mail->get_html_of_each_product($order, $product_id, $order->id, $licenseKey);

                debug_message(105, $order,  __FUNCTION__, 'Done processing email for license key:'.$licenseKey);

				$template .= "<br />$email_template<br />";
			}
			
			$default_footer = $qlm_mail->show_default_footer();
			$email_footer = get_option( 'qlm_footer', $default_footer );
			
			$send_email_html = $email_header.$template.$email_footer;

			
			
			$qlm_mail->send_email($order, $user_email, $send_email_html, $email_subject);
			
		}

		//adds metabox on order detail screen
		function add_meta(){		
			add_meta_box(
				'qlm_license_key',
				__( 'License Keys' ),
				array(&$this,'lincense_key_meta_box'),
				'shop_order',
				'advanced',
				'default'
			);
		}

		//fills meta box in conjection with above
		function lincense_key_meta_box($post){
			global $woocommerce;
						
			?>
			
			<div style="border: 1px solid lightgray;padding:1em;margin-bottom: 0.5em;"><p><?php echo qlm_get_order_meta( $post->ID, '_qlm_license_keys', true ); ?></p></div>
			<?php 

		}

		function is_qlm_item($order, $product) {

			global $woocommerce;

			$qlm_categories = get_option( 'qlm_categories');

            debug_message(110, $order,  __FUNCTION__, 'Determining if Product needs processing - order id:'.$product['name']);
			debug_message(115, $order,  __FUNCTION__, 'QLM Categories:'.$qlm_categories);

			if ($qlm_categories == '') return true;

			$qlm_categories_array = explode(',', $qlm_categories);
			if (empty($qlm_categories_array))
			{
				$qlm_categories_array = explode(';', $qlm_categories);
			}
			
			$product_id = $product['product_id'];  

			foreach($qlm_categories_array as $qlm_cat)
			{
				$qlm_cat = trim ($qlm_cat);

				if ( has_term( $qlm_cat, 'product_cat', $product_id ) ) 
				{
                    debug_message(120, $order,  __FUNCTION__, 'Detected product '.$product['name'] .' with category: '.$qlm_cat.' as a QLM item.');
					return true;
				}
				else
				{
                    debug_message(125, $order,  __FUNCTION__, 'Product '.$product['name'] .' is not a QLM item.');
				}
			}

			return false;
	
		}

		function order_status_changed( $order_id ) 
		{
			$order = wc_get_order( $order_id );
			debug_message(130, $order,  __FUNCTION__, 'Processing order_status_changed.');

			if ($this->are_all_products_downloadable ($order))
			{
				// we will process the request in the order_status_completed action
				debug_message(135, $order,  __FUNCTION__, 'Skipping order_status_changed.');
			}
			else 
			{
				$this->process_order ( $order );
			}
		}

		function order_status_completed ( $order_id ) 
		{
			$order = wc_get_order( $order_id );
			debug_message(140, $order,  __FUNCTION__, 'Processing order_status_completed.');

			$qlm_process_order_on_status_completed = get_option('qlm_process_order_on_status_completed');

			// check if we already processed this order, i.e. was order_status_changed already triggered
			$license_keys_metadata = qlm_get_order_meta ($order->id, '_qlm_license_keys', true);
			$license_keys_already_generated = true;
			if (is_null($license_keys_metadata) || $license_keys_metadata == '')
			{
				// if the order already has the license key, it means that we already processed it on the status changed event				
				$license_keys_already_generated = false;
			}

			debug_message(141, $order,  __FUNCTION__, 'qlm_process_order_on_status_completed:'.$qlm_process_order_on_status_completed.' License Keys Data:'.$license_keys_metadata);

			// If process order on status completed is true and no license keys have been created, try to process the order now
			if (($qlm_process_order_on_status_completed == true)  && ($license_keys_already_generated == false))
			{
				debug_message(142, $order,  __FUNCTION__, 'Processing this order now.');
				$this->process_order ( $order );
			}
			else 
			{
				debug_message(143, $order,  __FUNCTION__, 'Skipping processing order on orer_status_completed');				
			}
				
			$this->mail_on_order_completed ($order);

			debug_message(142, $order,  __FUNCTION__, 'Completed order_status_completed.');
		}

		// Check if all the products in the order are downloadable
		function are_all_products_downloadable ($order)
		{
			return false;			
			debug_message(150, $order,  __FUNCTION__, 'Start');

			$items = $order->get_items();

			foreach($items as $item)
			{
				$product_id = $item->get_product_id();
				$product = wc_get_product ($product_id);

				if ($product->is_downloadable() == false)
				{
					debug_message(155, $order,  __FUNCTION__, 'Found one product not downloadable: '.$item['name']);
					return false;
				}
			}

			debug_message(160, $order,  __FUNCTION__, 'All products are downloadable.');

			return true;
		}

		
		
		//Woocommerce calls this function when order status changes.
		function process_order ( $order ) 
		{
			global $woocommerce;

			//instantiate api calss
			$qlm_v = new QLM_Api_View();
			
			$date_time = date('Y-m-d H:i:s');
			$orig_order_id="";

			//create order object
			$order = new WC_Order($order->id);

			debug_message(165, $order,  __FUNCTION__, 'Processing order id:'.$order->id);
			debug_message(166, $order,  __FUNCTION__, 'UserData1 :'.$qlm_v->userdata1);

			change_role_on_purchase( $order);

			// The wcs_order_contains_renewal API is only available if WooCommerce Subscription is installed
			// Check if the function exists first
			if( $this->woo_subscriptions_enabled ($order))
			{
				debug_message(170, $order,  __FUNCTION__, 'function wcs_order_contains_renewal exists.');

				// Get first parent order
				if ( wcs_order_contains_renewal( $order->id ) )
				{
					debug_message(175, $order,  __FUNCTION__, 'Detected renewal order id:'.$order->id);
					//$parent_order_id = WC_Subscriptions_Renewal_Order::get_parent_order_id( $order->id );

					$subscriptions = wcs_get_subscriptions_for_renewal_order( $order->id );       
				  
					foreach ( $subscriptions as $subscription )
					{
						debug_message(180, $order,  __FUNCTION__, 'subscription id:'.$subscription->id);

						//$parent_order_id = WC_Subscriptions_Renewal_Order::get_parent_order_id( $subscription->id );
						$orig_subscription = $subscription;
						$orig_order_id = $subscription->order->id;

						debug_message(185, $order,  __FUNCTION__, 'parent order id:'.$orig_order_id);

						// we only support one subscription
						break;
					}
				}
				else
				{
					$subscriptions = wcs_get_subscriptions_for_order( $order->id );

					foreach ( $subscriptions as $subscription )
					{
						debug_message(190, $order,  __FUNCTION__, 'subscription id:'.$subscription->id);

						//$parent_order_id = WC_Subscriptions_Renewal_Order::get_parent_order_id( $subscription->id );
						$orig_subscription = $subscription;
                    
						if (is_null($orig_order_id))
						{
							debug_message(194, $order,  __FUNCTION__, 'parent order id: Null');
						}
						else
						{
							debug_message(195, $order,  __FUNCTION__, 'parent order id:'.$orig_order_id);
						}

						// we only support one subscription
						break;

					}

					debug_message(200, $order,  __FUNCTION__, 'Did not detect renewal order id:'.$order->id);
				}
			}
			else
			{
				debug_message(205, $order,  __FUNCTION__, 'Function  wcs_order_contains_renewal does not exist.');
			}

			debug_message(210, $order,  __FUNCTION__, 'Processing order id:'.$order->id);


			/*if we have parent order that means current order is a recurring order
			So let's call relevant function to handle recurring orders */
			if(!empty($orig_order_id))
			{
				debug_message(225, $order,  __FUNCTION__, 'Processing recurring order - order id:'.$order->id);
				$this->process_recurring_subscription($order, $orig_subscription);
				return;
			}

			debug_message(230, $order,  __FUNCTION__, 'Processing non-recurring order - order id:'.$order->id);

			/*we will have something in $recurring if this is a recurring parent order
			as we have already sent any recurring child order in the previous if */
			// Ralph - recurring orders were handled above

			//we have a recurring first order
			// TODO - not used
			$recurring = $order->get_total();
			//$recurring = '';

			debug_message(235, $order,  __FUNCTION__, 'Getting Total - order id:'.$order->id .' Recurring value:'.$recurring );

			//get all items of an order
			$items = $order->get_items();

			debug_message(240, $order,  __FUNCTION__, 'Getting Items - order id:'.$order->id);

			$user_id = $order->user_id;

			debug_message(245, $order,  __FUNCTION__, 'Getting UserID - order id:'.$order->id);

			if($user_id == 0)
			{
				debug_message(250, $order,  __FUNCTION__, 'Customer is not logged in - Will use billing information - Order ID:'.$order->id);
				//return;  //do not proceed if user is not logged in
			}

			debug_message(255, $order,  __FUNCTION__, 'Starting to process items - order id:'.$order->id);

			$this->process_items ($qlm_v, $order);

		}

		function process_items ($qlm_v, $order)
		{
			//$subscriptions = wcs_get_subscriptions_for_order( $order->id );
			$items = $order->get_items();

			$set_order_completed = true;

			foreach($items as $item)
            {
				if ($this->is_qlm_item ($order, $item) == true) 
				{
					$result = $this->process_item ($qlm_v, $order, $item);

					if($result == false)
					{
						$set_order_completed = false;
						debug_message(555, $order,  __FUNCTION__, 'The order will not be set to Completed because one item was not successfully processed:'.$item['name']);	
					}
				}
				else 
				{
					$set_order_completed = false;
					debug_message(300, $order,  __FUNCTION__, 'Skipping non-qlm item:'.$item['name']);
				}
			}

			/* if we are here, it means everything went well
			    if we do not set the order status, the license key is not getting displayed 
				on the order confirmation web page, not is it shown in the email sent to the customer */
					
					

			// We only update the status if all products in the order are QLM related
			if ($set_order_completed == true)
			{
				if ($this->are_all_products_downloadable ($order) == false)
				{
					debug_message(260, $order,  __FUNCTION__, 'Webhook Order Status Changed. Setting Status to Completed.');		
					$order->update_status('completed');	
				}
				else 
				{
					debug_message(265, $order,  __FUNCTION__, 'Webhook Order Status Changed. Setting Status to Completed.');		
					$order->update_status('completed');	
				}
			}
		}

		function woo_subscriptions_enabled ($order)
		{
			if( function_exists( 'wcs_order_contains_renewal' ) )
            {
				return true;
			}
			else 
			{
				debug_message(530, $order,  __FUNCTION__, 'WooCommerce Subscriptions is not installed.');		
				return false;
			}
		}

		function get_subscription ($order, $order_item)
		{
			if ($this->woo_subscriptions_enabled($order) == false)
			{
				return null;
			}

			if ( wcs_order_contains_renewal( $order->id ) )
            {
				$subscriptions = wcs_get_subscriptions_for_renewal_order( $order->id );     
			}
			else 
			{
				$subscriptions = wcs_get_subscriptions_for_order( $order->id );
			}

			$item_product_id = $order_item['product_id'];  

			foreach ( $subscriptions as $subscription )
			{
				$items = $subscription->get_items();

				debug_message(270, $order,  __FUNCTION__, 'Subscription ID: '.$subscription->id);


				foreach ($items as $item)
				{
					debug_message(275, $order,  __FUNCTION__, 'Subscription ID: '.$subscription->id.' Item id:'.$item_product_id);

					if ($item['product_id'] == $item_product_id)
					{
						debug_message(280, $order,  __FUNCTION__, 'Found match:'.$subscription->id);

						return $subscription;
					}					

				}
			}

			debug_message(285, $order,  __FUNCTION__, 'No match.');

			return null;
		}

		function is_variation ($item)
		{
			if($item['variation_id'] != 0 )
			{
				return true;
			}
			else {
				return false;
			}
		}


		function process_item ($qlm_v, $order, $item)
		{

			debug_message(290, $order,  __FUNCTION__, 'Processing item  - order id:'.$order->id);

			$subscription = $this->get_subscription ($order, $item);
			

			if($this->is_variation ($item) == true)
            {
				$res = $this->process_variation_item ($qlm_v, $order, $item, $subscription);
			}
            else
            {
				$res = $this->process_simple_item ($qlm_v, $order, $item, $subscription);
        	}

			//check return status and handle error
			return $this->process_result ($qlm_v, $order, $item, $res);
				
		}

		function process_result ($qlm_v, $order, $item, $res)
		{
			if($res['status'] == 'error')
            {
				$msg = $date_time.' '.$res['err_msg'];
				$order->add_order_note($msg);

				debug_message(550, $order,  __FUNCTION__, 'License Server Result: '.$res['err_msg']);
				return false;
			}
            else
            {
				$license_key = $res['license_key'];
					
				$licenseKeyFieldName = get_option( 'qlm_licenseKeyFieldName');
				if(empty($licenseKeyFieldName)) $licenseKeyFieldName = 'licenseKey';

				debug_message(310, $order,  __FUNCTION__, 'License caption: '.$licenseKeyFieldName);

                $item[$licenseKeyFieldName] = $license_key;
                $title = $item['name'];

                $tmpKey = $item[$licenseKeyFieldName];
                debug_message(315, $order,  __FUNCTION__, 'Created Key:'.$tmpKey.' for item:'.$title);

				$rest = substr($title, 0, 35);
				$new_meta = "$rest: $license_key";
				//get post meta
				//oldvalue + Title + $licensekey
				if($this->is_variation ($item) == true)
                {
					//saves license keys if product is a variable product
					$variation_id = $item['variation_id'];

                    debug_message(320, $order,  __FUNCTION__, 'Finalizing variation ID:'.$variation_id.' for item:'.$title);

					//saves individual keys i.e one for each product.
					qlm_update_order_meta($order->id, '_qlm_license_key_'.$variation_id, $license_key);

					$order->save();

					debug_message(321, $order,  __FUNCTION__, 'Updated metadata for:'.$variation_id.' item:'.$title);

					$old_meta = qlm_get_order_meta ($order->id, '_qlm_license_keys', true);
					if($old_meta)
                    {
						$update_meta = $old_meta.'<br />'.$new_meta;
					}
                    else
                    {
						$update_meta = $new_meta;
					}

					//saves concatednated and html added keys for all products for display and email
					qlm_update_order_meta($order->id, '_qlm_license_keys', $update_meta);

					debug_message(322, $order,  __FUNCTION__, 'Updated metadata for all products');
				}
                else
                {
					//saves license keys if product is a simple product
					$product_id = $item['product_id'];

					debug_message(325, $order,  __FUNCTION__, 'Finalizing simple product  ID:'.$product_id.' for item:'.$title);

                    // we save one license key per product ID
					qlm_update_order_meta($order->id, '_qlm_license_key_'.$product_id, $license_key);
					$order->save();

					$old_meta = qlm_get_order_meta ($order->id, '_qlm_license_keys', true);
						
					if($old_meta)
                    {
						$update_meta = $old_meta.'<br />'.$new_meta;
					}
                    else
                    {
						$update_meta = $new_meta;
					}
						
					qlm_update_order_meta($order->id, '_qlm_license_keys', $update_meta);
				}
			}

			return true;
		}

		function get_next_payment_date_from_wc ($order, $subscription)
		{
			if (is_null($subscription))
			{
				debug_message(566, $order,  __FUNCTION__, 'Subscription is null.');
				return null;
			}
			else
			{
				$next_payment_get_date_str = $subscription->get_date ('next_payment');
				if (($next_payment_get_date_str != '') && ($next_payment_get_date_str != '0'))
				{
					$next_payment_get_date = new DateTime($next_payment_get_date_str);
				}
				debug_message(570, $order,  __FUNCTION__, 'get_date(next_payment):'.$next_payment_get_date_str);

				$next_payment_calc_date_str = $subscription->calculate_date ('next_payment');
				if (($next_payment_calc_date_str != '') && ($next_payment_calc_date_str != '0'))
				{
					$next_payment_calc_date = new DateTime($next_payment_calc_date_str);
				}
				debug_message(575, $order,  __FUNCTION__, 'calculate_date(next_payment):'.$next_payment_calc_date_str);

				if (is_null($next_payment_get_date) && is_null($next_payment_calc_date))
				{
					debug_message(580, $order,  __FUNCTION__, 'WC returned empty dates.');
					return null;
				}
				else if (is_null($next_payment_get_date))
				{
					debug_message(585, $order,  __FUNCTION__, 'Using date:'.$next_payment_calc_date_str);	
					$next_payment_date_str = $next_payment_calc_date_str;
				}
				else if (is_null($next_payment_calc_date))
				{
					debug_message(590, $order,  __FUNCTION__, 'Using date:'.$next_payment_get_date_str);	
					$next_payment_date_str = $next_payment_get_date_str;
				}
				else 
				{
					if ( wcs_order_contains_renewal( $order->id ) )
					{
						debug_message(591, $order,  __FUNCTION__, 'This is a renewal of an existing subscription.');	

						// we have 2 dates, take the largest one
						if ($next_payment_calc_date > $next_payment_get_date)
						{
							$next_payment_date_str = $next_payment_calc_date_str;
						}
						else 
						{
							$next_payment_date_str = $next_payment_get_date_str;
						}
					}
					else 
					{
						debug_message(592, $order,  __FUNCTION__, 'This is the first order of a new subscription.');	
						// this is a first order of a subscription
						$next_payment_date_str = $next_payment_get_date_str;
					}
					
					debug_message(595, $order,  __FUNCTION__, 'Using date:'.$next_payment_date_str);	
									
				}

				$next_payment_date = new DateTime($next_payment_date_str);
				return $next_payment_date;
				
			}
			return null;
		}

		function process_simple_item ($qlm_v, $order, $item, $subscription)
		{						
			$recurring = $order->get_total();
			$qty = $item['qty'];
			$qlm_activation_key = get_item_addon_value($order, $item, 'qlm_activation_key');			
			$yearly = 0;
			debug_message(418, $order,  __FUNCTION__, 'Activation Key: '.$qlm_activation_key);
			
			debug_message(419, $order,  __FUNCTION__, 'Processing simple  item:'.$item);

			if ($subscription != null)
			{				
				$qlm_object = new qlm_object();
				$this->calculate_expiry_date ($order, $subscription, $qlm_object, $item);


				$res = $qlm_v->send_request($order, $item, $order->user_id, $qty, $yearly, $qlm_object->expiry_duration, $qlm_object->expiry_date, "", $subscription, $qlm_activation_key);
			}
			else 
			{
				debug_message(421, $order,  __FUNCTION__, 'No subscription is associated with this order:'.$order->id);

					// Do not send a subscriptionID if this is not a subscription Product. $subscription may have a value if the order contains
				// a combination of subscription products and regular products so we must clear it for the regular product by passing '' as the last argument to send_request
        		$res = $qlm_v->send_request($order, $item, $order->user_id, $qty, $yearly, -1, null, "", '', $qlm_activation_key);
			}

			debug_message(365, $order,  __FUNCTION__, 'Processing Simple product - Completed call to License Server: '.$res['status']);

			return $res;
		}

		function get_maintenance_plan_attribute_name()
		{
			$mpi= get_option( 'qlm_maintenance_plan_identifier');
			if (is_null ($mpi) == false)
			{
				$mpi = strtolower($mpi);
				$mpi = str_replace (" ", "-", $mpi);
				$mpi = "attribute_".$mpi;
			}
			else 
			{
				$mpi = "attribute_maintenance-plan";
			}

			return $mpi;
		}

		function process_variation_item ($qlm_v, $order, $item, $subscription)
		{
			$recurring = $order->get_total();
			$qty = $item['qty'];
			$qlm_activation_key = get_item_addon_value($order, $item, 'qlm_activation_key');

			debug_message(295, $order,  __FUNCTION__, 'Activation Key: '.$qlm_activation_key);
			

            debug_message(370, $order,  __FUNCTION__, 'Processing variation item:'.$item['variation_id']);

            //We will land here if product is a variable product

            $prod = new WC_Product_Variation( $item['variation_id'] );
            $attributes = $prod->get_variation_attributes();

			$is_args = $prod->get_sku();

			debug_message(375, $order,  __FUNCTION__, 'Found sku: name= '.$is_args);

			//if (substr( $is_args, 0, 1 ) != "&")
			//{
				// if the SKU does not start with &, it's not ours
				//$is_args = "";
			//}
            debug_message(380, $order,  __FUNCTION__, 'Processing variation item recurring value:'.$recurring);

            // Ralph - This is not true, we do not always have a maintenance plan
			//if(! isset($attributes['attribute_maintenance-plan'])) continue; //we always have maintenence plan in a variation, so skip if this variation is not maintenance plan variation.

			//Set maintenance plan option for api call accordingly
			if($attributes[$this->get_maintenance_plan_attribute_name()] == 'Yes')
            {
				debug_message(600, $order,  __FUNCTION__, 'Maintenance Plan is selected.');
				$yearly = 1;
			}
            else
            {
				debug_message(605, $order,  __FUNCTION__, 'No Maintenance Plan selected.');
				$yearly = 0;
			}

			if ($subscription != null)
			{
				$qlm_object = new qlm_object();
				$this->calculate_expiry_date ($order, $subscription, $qlm_object, $item);

                debug_message(405, $order,  __FUNCTION__, 'Detected recurring variable product. Product:'.$item['product_id'].' Frequency:'.$sp.' Interval:'.$sinterval.' Duration:'.$qlm_object->expiry_duration);

				$res = $qlm_v->send_request($order, $item, $order->user_id, $qty, $yearly, $qlm_object->expiry_duration, $qlm_object->expiry_date, $is_args, $subscription, $qlm_activation_key);
			}
            else
            {
                debug_message(410, $order,  __FUNCTION__, 'Detected non recurring variable product. Product:'.$item['product_id'].' Frequency:'.$sp.' Interval:'.$sinterval.' Duration:'.$qlm_object->expiry_duration.' ProductName:'.$product_name);

				// Do not send a subscriptionID if this is not a subscription Product. $subscription may have a value if the order contains
				// a combination of subscription products and regular products so we must clear it for the regular product by passing '' as the last argument to send_request
    			$res = $qlm_v->send_request($order, $item, $order->user_id, $qty, $yearly, -1, null, $is_args, '', $qlm_activation_key);
    		}

			debug_message(415, $order,  __FUNCTION__, 'Processing variable product - Completed call to License Server: '.$res['status']);

			return $res;
		}
		/*
			This function is only for processing of a recurrung order			
			$order   It is the current order. It must be a recurring order
			$subscription is the current subscription
		*/
		function process_recurring_subscription($order,  $subscription )
        {
			global $woocommerce;

			debug_message(540, $order,  __FUNCTION__, 'Starting to process recurring subscription');

			$qlm_v = new QLM_Api_View();
			
			$date_time = date('Y-m-d H:i:s');
						
			$items = $order->get_items();
			
			$user_id = $order->user_id;
			if($user_id == 0) 
			{
				debug_message(534, $order,  __FUNCTION__, 'No user associated to the subscription. We cannot process a subscription without a user.');
				return;
			}

			

			$set_order_completed = true;
			
			foreach($items as $item)
			{

				if ($this->is_qlm_item ($order, $item) == false) 
				{
					$set_order_completed = false;
					debug_message(489, $order,  __FUNCTION__, 'Detecting one non-qlm item in the order. We will not set the Setting Status to Completed. Item:'.$item);										
					continue;
				}

				debug_message(491, $order,  __FUNCTION__, 'Starting to process subscription item:'.$item['name']);

				$qlm_result = $this->process_recurring_subscription_item ($order, $subscription, $item);

				if ($qlm_result == true)
				{

					// we will only process the first item since we will use the Subscription ID to perform the renewal
					// The QLM License Server takes care of renewing each item in the subscription

					// update the order status if the products are not all downloadable - process recurring subscription
					if (($set_order_completed == true) && ($this->are_all_products_downloadable ($order) == false))
					{
						//debug_message(485, $order,  __FUNCTION__, 'QLM -process_recurring_subscription - Set Order Status to Pending.');										
						//$current_status = $order->get_status();	
						debug_message(490, $order,  __FUNCTION__, 'Processing Recurring subscription. Setting Status to Completed');										
						$order->update_status('completed');	
					}				
					else 
					{
						debug_message(491, $order,  __FUNCTION__, 'Processing Recurring subscription. Skipping Status to Completed');				
					}
				}
				else
				{
					debug_message(492, $order,  __FUNCTION__, 'Processing Recurring subscription.  Status was not set Completed because the QLM License Server return an error.');
				}


				return;
			}			
		}

		function calculate_expiry_date ($order, $subscription, $qlm_object, $item)
		{

			debug_message(329, $order,  __FUNCTION__, 'calculate_expiry_date');

			$next_payment_date = $this->get_next_payment_date_from_wc ($order, $subscription);
			if (!is_null($next_payment_date))
			{
				$qlm_object->expiry_date = $next_payment_date->format('Y-m-d');
                $use_next_payment_date = true;
				return $qlm_object->expiry_date;
			}
			
			debug_message(328, $order,  __FUNCTION__, 'WC returned a null expiry date. We will calculate it.');

			// Calculate the duration

			if (method_exists ( 'WC_Subscriptions_Product', 'get_period'))
            {

				if (QLM_DEBUG  == true)
				{
					$order_date = new DateTime(date());
					$order_date_str = date_format ($order_date, 'Y-m-d H:i:s');
					debug_message(330, $order,  __FUNCTION__, 'Order Date: '.$order_date_str);
				
					$next_payment_date_str = date_format ($next_payment_date, 'Y-m-d H:i:s');
					debug_message(331, $order,  __FUNCTION__, 'Next Date: '.$next_payment_date_str);

				

					$diff = date_diff ($order_date, $next_payment_date);				

					if ($diff->days < 0)
					{
						// early renewal
						debug_message(331, $order,  __FUNCTION__, 'Late Renewal 1: '.$diff->days);
					}
					else if ($diff->days > 0)
					{
						debug_message(331, $order,  __FUNCTION__, 'Early Renewal 2: '.$diff->days);
					}
					else 
					{
						debug_message(331, $order,  __FUNCTION__, 'On time renewal 3: '.$diff->days);
					}
				}



				if($item['variation_id'] != 0)
				{
					$sp = WC_Subscriptions_Product::get_period( $item['variation_id'] );
				}
				else 
				{
					$sp = WC_Subscriptions_Product::get_period( $item['product_id'] );
				}

                debug_message(335, $order,  __FUNCTION__, 'Subscription Period: ' .$sp);
            }
            else
            {
                debug_message(340, $order,  __FUNCTION__, 'WC_Subscriptions_Product::get_period does not exist.');
            }


			if($item['variation_id'] != 0)
			{		
				$sinterval = get_post_meta($item['variation_id'], '_subscription_period_interval', true);
			}
			else 
			{
				$product_id = $item['product_id'];				
				$sinterval = get_post_meta($product_id, '_subscription_period_interval', true);
            }

          
			debug_message(455, $order,  __FUNCTION__, 'Calculating Expiry date of product: '.$product_id.' - Interval: '.$sinterval);
                    
			$qlm_object->expiry_duration = -1;

			if ($sp != null)
			{
				switch($sp)
				{
					case 'day':
						$qlm_object->expiry_duration = $sinterval;
						break;
					case 'week':
						$qlm_object->expiry_duration = $sinterval * 7;
						break;
					case 'month':
						$qlm_object->expiry_duration = $sinterval * 31;
						break;
					case 'year':
						$qlm_object->expiry_duration = $sinterval * 365;
						break;
				}
			}
			else 
			{
				$qlm_object->expiry_duration = -1;	
			}
			
			$tmpdate = new DateTime($order->order_date);
			$tmpdate->modify ('+'.$qlm_object->expiry_duration.' day');

			$qlm_object->expiry_date = $tmpdate->format ('Y-m-d');		
			


			return $qlm_object->expiry_date;
		}

		function process_recurring_subscription_item ($order, $subscription, $item)
		{
			debug_message(540, $order,  __FUNCTION__, 'Start processing item: '.$item['name']);

			$qlm_v = new QLM_Api_View();
			
			$qty = $item['qty'];
				
			if($item['variation_id'] != 0)
			{													
				debug_message(425, $order,  __FUNCTION__, 'Processing Item:'.$item);

				$qlm_object = new qlm_object();

				$this->calculate_expiry_date ($order, $subscription, $qlm_object, $item);

				debug_message(435, $order,  __FUNCTION__, 'OrderDate: '.$order->order_date.' - Expiry Date: '.$qlm_object->expiry_date );
					
				$prod = new WC_Product_Variation( $item['variation_id'] );
				$attributes = $prod->get_variation_attributes();					
					
				if($attributes[$this->get_maintenance_plan_attribute_name] == 'Yes')
				{
					$yearly = 1;					
				}
				else
				{
					$yearly = 0;					
				}

				$res = $qlm_v->send_recurring_request($order, $item, $order->user_id, $qty, $qlm_object->expiry_duration, $qlm_object->expiry_date, $yearly, $subscription);

				debug_message(440, $order,  __FUNCTION__, 'Processing variable product - Completed call to License Server: '.$res['status']);
					
			}
			else
			{
				//simple product
				$yearly = 0;
				$product_id = $item['product_id'];
				
				$qlm_object = new qlm_object();

				$this->calculate_expiry_date ($order, $subscription, $qlm_object, $item);
									
				debug_message(465, $order,  __FUNCTION__, 'OrderDate: '.$order->order_date.' - Expiry Date: '.$qlm_object->expiry_date);
          									
				$res = $qlm_v->send_recurring_request($order, $item, $order->user_id, $qty, $qlm_object->expiry_duration, $qlm_object->expiry_date, $yearly, $subscription);

				debug_message(475, $order,  __FUNCTION__, 'Processing Simple product - Completed call to License Server: '.$res['status']);

			}				
				
			if($res['status'] == 'error')
			{
				$msg = $date_time.' '.$res['err_msg']; 
				$order->add_order_note($msg);				
					
				debug_message(480, $order,  __FUNCTION__, 'Order failed: '.$msg);
				return false;
			}
			else
			{
				$license_key = $res['license_key'];
				$title = $item['name'];
				$rest = substr($title, 0, 35); 
				$new_meta = "$rest: $license_key";

				debug_message(545, $order,  __FUNCTION__, 'Result from QLM License Server: '.$license_key);
					
				//get post meta
				//oldvalue + Title + $licensekey

				if($item['variation_id'] != 0)
				{
					$variation_id = $item['variation_id'];
						
					$old_meta = qlm_get_order_meta ($order->id, '_qlm_license_keys', true);
					if($old_meta)
					{
						$update_meta = $old_meta.'<br />'.$new_meta;
					}else{
						$update_meta = $new_meta;
					}
						
					qlm_update_order_meta($order->id, '_qlm_license_keys', $update_meta);
				}
				else
				{
					$product_id = $item['product_id'];
						
					$old_meta = qlm_get_order_meta($order->id, '_qlm_license_keys', true);

					if($old_meta)
					{
						$update_meta = $old_meta.'<br />'.$new_meta;
					}else {
						$update_meta = $new_meta;
					}
						
					qlm_update_order_meta($order->id, '_qlm_license_keys', $update_meta);
				}
			}

			return true;
		}

		//it is a wordpress function 
		function wp_enqueue_scripts(){
			wp_enqueue_script('jquery');
			wp_enqueue_script('js-fe-bootstrap',plugins_url('js/js-agile-bootstrap.js', __FILE__));
			wp_enqueue_style( 'agile-fe-bootstrap', plugins_url('css/agile-bootstrap.css', __FILE__) );
		}
		
		function install(){
			
		}
		
		//it is a wordpress function 
		function admin_menu(){
			
			add_menu_page('Settings', 'QLM', 'manage_options', 'mnu_qlm', array(&$this, 'setting_api'),plugins_url( 'quick-license-manager/images/certificate.png' ) );
			add_submenu_page('mnu_qlm', 'Settings', 'Settings' , 'manage_options','mnu_qlm', array(&$this, 'setting_api') );
			add_submenu_page('mnu_qlm', 'Email Templates', 'Email Templates' , 'manage_options','qlm_templates', array(&$this, 'qlm_email_templates') );
		}
		
		
		
		//it is a wordpress function 
		function wp_set_content_type(){

			return "text/html";

		}
		
		//This function shows/edits email template via the admin
		function qlm_email_templates(){
			$qlm_email_v =new QLM_Emails();
			
			if(isset($_POST['submit_template'])){
				$pid = $_POST['qlm_pid'];
				
				$email_subject = $_POST['qlm_html_subject'];
				update_option( 'qlm_subject', $email_subject );

				$email_header = $_POST['qlm_html_header'];
				update_option( 'qlm_header', $email_header );
				$email_html = $_POST['qlm_html_mail'];
				
				update_post_meta( $pid,'qlm_email_html',$email_html );
				$email_footer = $_POST['qlm_html_footer'];
				update_option( 'qlm_footer', $email_footer );
			}
			$qlm_category='qlm';
			$qlm_products=$this->get_products_by_catagory($qlm_category);
			$qlm_email_v->show_email_templates($qlm_products);
			
		}
		
		function get_products_by_catagory($qlm_category){
			$args = array(
				'posts_per_page' => -1,
				'product_cat' => $qlm_category,
				'post_type' => 'product',
				'orderby' => 'title',
			);
			$product_array =get_posts ( $args );
			return $product_array;
		}
		
		//this plugin settings main function. It saves API end point etc
		function setting_api(){
			$qlm_v = new QLM_Api_View();
			
			$this->handle_setting();
			$qlm_v->show_setting();
		}

		//Settings handler
		function handle_setting(){
			if(isset($_POST['qlm_submit_set'])){
				$end = $_POST['qlm_end_point'];
				$version = $_POST['is_qlmversion'];
				$licenseKeyFieldName = $_POST['qlm_licenseKeyFieldName'];
				$qlm_categories = $_POST['qlm_categories'];
				$qlm_user_role = $_POST['qlm_user_role'];
				$qlm_maintenance_plan_identifier = $_POST['qlm_maintenance_plan_identifier'];
				$qlm_webhook_secret_key = $_POST['qlm_webhook_secret_key'];				
				$user_id = $_POST['qlm_user_id'];
				$password = $_POST['qlm_password'];


				if (empty ($_POST['qlm_product_addon']))
				{
					$qlm_product_addon = false;
				}
				else 
				{
					$qlm_product_addon= $_POST['qlm_product_addon'];
				}

				if (empty ($_POST['qlm_revoke_when_order_cancelled']))
				{
					$qlm_revoke_when_order_cancelled = false;
				}
				else 
				{
					$qlm_revoke_when_order_cancelled= $_POST['qlm_revoke_when_order_cancelled'];
				}

				if (empty ($_POST['qlm_revoke_when_subscription_cancelled']))
				{
					$qlm_revoke_when_subscription_cancelled = false;
				}
				else 
				{
					$qlm_revoke_when_subscription_cancelled= $_POST['qlm_revoke_when_subscription_cancelled'];
				}

				if (empty ($_POST['qlm_next_payment_date_based_on_schedule']))
				{
					$qlm_next_payment_date_based_on_schedule = false;
				}
				else 
				{
					$qlm_next_payment_date_based_on_schedule= $_POST['qlm_next_payment_date_based_on_schedule'];
				}

				if (empty ($_POST['qlm_process_order_on_status_completed']))
				{
					$qlm_process_order_on_status_completed = false;
				}
				else 
				{
					$qlm_process_order_on_status_completed= $_POST['qlm_process_order_on_status_completed'];
				}

				if (empty ($_POST['qlm_send_mail']))
				{
					$qlm_send_mail = false;
				}
				else 
				{
					$qlm_send_mail= $_POST['qlm_send_mail'];
				}

				if (empty ($_POST['qlm_enable_log']))
				{
					$qlm_enable_log = false;
				}
				else 
				{
					$qlm_enable_log = $_POST['qlm_enable_log'];
				}


				update_option( 'qlm_end_point', $end );
				update_option( 'is_qlmversion', $version );
				update_option( 'qlm_licenseKeyFieldName', $licenseKeyFieldName );
				update_option( 'qlm_categories', $qlm_categories );
				update_option( 'qlm_user_role', $qlm_user_role );
				update_option( 'qlm_maintenance_plan_identifier', $qlm_maintenance_plan_identifier );				
				update_option( 'qlm_webhook_secret_key', $qlm_webhook_secret_key );
				update_option( 'qlm_product_addon', $qlm_product_addon );
				update_option( 'qlm_userid', $user_id );
				update_option( 'qlm_password', $password );
				update_option( 'qlm_revoke_when_order_cancelled', $qlm_revoke_when_order_cancelled );
				update_option( 'qlm_revoke_when_subscription_cancelled', $qlm_revoke_when_subscription_cancelled );
				update_option( 'qlm_next_payment_date_based_on_schedule', $qlm_next_payment_date_based_on_schedule );
				update_option( 'qlm_process_order_on_status_completed', $qlm_process_order_on_status_completed );
				update_option( 'qlm_send_mail', $qlm_send_mail );
				update_option( 'qlm_enable_log', $qlm_enable_log );
				
				echo "<h2>Your settings have been saved successfully.</h2>";
			}
		}

		/* OBSOLETE
			This function is only for processing of a recurring order
			$orig_order_id    is the parent order id  (first order)
			$order_id   It is the current order. It must be a recurring order
		*/
		function process_recurring($orig_order_id, $order_id,  $orig_subscription )
        {
			global $woocommerce;

			$qlm_v = new QLM_Api_View();
			
			$date_time = date('Y-m-d H:i:s');
			
			//current order object
			$order = new WC_Order($order_id);
			//first order object
			$orig_order = new WC_Order($orig_order_id);
			
			$items = $order->get_items();
			
			$user_id = $order->user_id;
			if($user_id == 0) return;
			
			foreach($items as $item)
			{
				$qty = $item['qty'];
				
				if($item['variation_id'] != 0)
				{
					//This product is a variable product and is a recurring product
					//Let's get its activation key from parent order

					$activation_key = qlm_get_order_meta($orig_order_id, '_qlm_license_key_'.$item['variation_id'], true);

					if(empty($activation_key)) continue; //if we don't have activation key we cannot renew current recurring order
					
					
					$sp = WC_Subscriptions_Product::get_period( $item['variation_id'] );
					debug_message(495, $order,  __FUNCTION__, 'Processing Item:'.$item.'Period'.$sp);
				
          
					$sinterval = get_post_meta($item['variation_id'], '_subscription_period_interval', true);
          
          
					debug_message(500, $order,  __FUNCTION__, 'Processing Item:'.$item.'Interval'.$sinterval);
          
					$exduration = 1;
					switch($sp){
						case 'day':
							$exduration = $sinterval;
							break;
						case 'week':
							$exduration = $sinterval * 7;
							break;
						case 'month':
							$exduration = $sinterval * 31;
							break;
						case 'year':
							$exduration = $sinterval * 365;
							break;
					}
					
					
					$exdate = WC_Subscriptions_Product::get_expiration_date( $item['variation_id'], $orig_order->order_date );
					
					$prod = new WC_Product_Variation( $item['variation_id'] );
					$attributes = $prod->get_variation_attributes();
					
					
					if($attributes[$this->get_maintenance_plan_attribute_name()] == 'Yes')
					{
						$yearly = 1;
						$res = $qlm_v->send_recurring_request($order, $item, $order->user_id, $qty, $exduration, $exdate, $activation_key, $yearly, $orig_subscription);
					}
					else
					{
						$yearly = 0;
						$res = $qlm_v->send_recurring_request($order, $item, $order->user_id, $qty, $exduration, $exdate, $activation_key, $yearly, $orig_subscription);
					}
					
				}
				else
				{
					//simple product
					$yearly = 0;
					$product_id = $item['product_id'];
					$activation_key = qlm_get_order_meta($orig_order_id, '_qlm_license_key_'.$product_id, true);
          
					if(empty($activation_key))  
					{
					    debug_message(505, $order,  __FUNCTION__, 'Skipping product because no activation key was found. Product ID: '.$product_id);

						continue;
					}
									
					$sp = WC_Subscriptions_Product::get_period( $product_id );

					debug_message(510, $order,  __FUNCTION__, 'Processing Simple product: '.$product_id.'Period: '.$sp);
          
          
					$sinterval = get_post_meta($product_id, '_subscription_period_interval', true);
          
    
					debug_message(515, $order,  __FUNCTION__, 'Processing Simple product: '.$product_id.'Interval: '.$sinterval);
          
          
					$exduration = 1;
					switch($sp)
					{
						case 'day':
							$exduration = $sinterval;
							break;
						case 'week':
							$exduration = $sinterval * 7;
							break;
						case 'month':
							$exduration = $sinterval * 31;
							break;
						case 'year':
							$exduration = $sinterval * 365;
							break;
					}
          
					debug_message(520, $order,  __FUNCTION__, 'Processing Simple product: '.$product_id.'Exp Duration: '.$exduration);
          
					$exdate = WC_Subscriptions_Product::get_expiration_date( $product_id, $orig_order->order_date );
					
					$res = $qlm_v->send_recurring_request($order, $item, $order->user_id, $qty, $exduration, $exdate, $activation_key, $yearly=0, $orig_subscription);

				}
				
				
				if($res['status'] == 'error')
				{
					$msg = $date_time.' '.$res['err_msg']; 
					$order->add_order_note($msg);
					continue;
				}
				else
				{
					$license_key = $res['license_key'];
					$title = $item['name'];
					$rest = substr($title, 0, 35); 
					$new_meta = "$rest: $license_key";
					
					//get post meta
					//oldvalue + Title + $licensekey

					if($item['variation_id'] != 0)
					{
						$variation_id = $item['variation_id'];
						
						qlm_update_order_meta($order_id, '_qlm_license_key_'.$variation_id, $activation_key);
						$order->save();

						$old_meta = qlm_get_order_meta($order_id, '_qlm_license_keys', true);

						if($old_meta)
						{
							$update_meta = $old_meta.'<br />'.$new_meta;
						}else{
							$update_meta = $new_meta;
						}
						
						qlm_update_order_meta($order_id, '_qlm_license_keys', $update_meta);
					}
					else
					{
						$product_id = $item['product_id'];
						
						qlm_update_order_meta($order_id, '_qlm_license_key_'.$product_id, $activation_key);
						$order->save();
						
						$old_meta = qlm_get_order_meta($order_id, '_qlm_license_keys', true);

						if($old_meta)
						{
							$update_meta = $old_meta.'<br />'.$new_meta;
						}else {
							$update_meta = $new_meta;
						}				
						
						qlm_update_order_meta($order_id, '_qlm_license_keys', $update_meta);
					}
										
					/*
					debug_message(525, $order,  __FUNCTION__, 'Setting Order  Status to Completed.');										
					$order->update_status('completed');	*/					
				}
			}			
		}

	}// class ends
}//if ends

new QLM_Main();
