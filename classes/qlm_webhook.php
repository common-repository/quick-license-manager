<?php
defined('ABSPATH')||die('No Script Kiddies Please');

/**
* Class Contains functions for REST API
*/

class QlmWebhook{
    /**
     * Inits Endpoint for API to listen
     */
    public function init_route(){

        register_rest_route(
            'wc-qlm-webhooks/v1',
            '/renew-subscription',
            array(
                'methods'=>'POST',
                'callback'=>array($this,'process_subscribe_request'),
            )
        );

    }

   /**
    * Callback function for Endpoint
    * @param - $request
    */
    function process_subscribe_request(WP_REST_Request $request){
        $parameters = $request->get_params();
        $signature = $request->get_header('x-qlm-signature');
        $verify_request=verify_signature($signature,$request->get_body());
        if(!$verify_request){
            wp_send_json(array('error'=>'true','msg'=>'request not verified'),403);
        }
        $verify_params=$this->verify_params($parameters);
        if($verify_params['error']){
            wp_send_json(array('error'=>true,'msg'=>$verify_params['msg']),400);
        }

        $process_subscription_status=$this->process_subscription($parameters);
        wp_send_json(array('error'=>false,'subscription'=>$process_subscription_status));
    }

    /**
     * Verifies Parameter Included With Request
     * @param - $parameters - Array of parameters included in request
     * @return - Array with key 'error' and 'msg'
     */

     function verify_params($parameters){
         $error=false;
         $msg="";
         if(array_key_exists('LicenseInfo',$parameters)&&is_array($parameters['LicenseInfo'])&&array_key_exists('SubscriptionID',$parameters['LicenseInfo'])){
            $subscription_id=$parameters['LicenseInfo']['SubscriptionID'];
            $subscription_post=get_post($subscription_id);
            if(empty($subscription_post)||$subscription_post->post_type!='shop_subscription'){
                $error=true;
                $msg="SubscriptionID provided in request doesn't correspond to a valid Subscription";
            }
         }
         else{
             $error=true;
             $msg="Request Body doesn't follow defined structure";
         }

        return array('error'=>$error,'msg'=>$msg);
     }

     /**
      * Process Subscription For Valid Request
      * @param - $parameters -Array of requested parameters
      * @return string
      */
    
    /**
     * 
     * 
     */
    function process_subscription($parameters){
        $status="";
        $order_ids=array();
        $subscription_id=$parameters['LicenseInfo']['SubscriptionID'];
        $subscription = new WC_Subscription($subscription_id);
        $pending_order_ids=get_pending_orders($subscription);
        if(empty($pending_order_ids)){
            $renewal_order=wcs_create_renewal_order($subscription);
            if(!is_wp_error($renewal_order)){
                $renewal_order->set_payment_method_title( 'Direct bank transfer' );
				$renewal_order->save();
                //Put the subscription on hold
                $order_note = _x( 'Subscription renewal payment due:', 'used in order note as reason for why subscription status changed', 'woocommerce-subscriptions' );
                $subscription->update_status( 'on-hold', $order_note );
                $order_id=$renewal_order->get_id();
                trigger_mail($order_id);
                $order_ids=array($order_id);
                $status="new";
            }
        }
        else{
            $status="pending_order_exists";
            $order_ids=$pending_order_ids;
            
            debug_message(5, $subscription, __FUNCTION__ , 'Processing Manual Subscription Renewal: a pending order already exists:'.$pending_order_ids[0]);

        }
        return array('status'=>$status,'order'=>$order_ids);
    }

    


}

?>