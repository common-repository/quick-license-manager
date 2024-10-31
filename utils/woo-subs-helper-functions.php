<?php
    defined('ABSPATH')||die('No Script Kiddies Please!');

    /**
     * Signature Verification Parameters
     * @param $signature - Signature Included in header
     * @return bool
     */

     function verify_signature($signature,$body){
        $hash=get_hash($body);
        //print_r($hash);
        if($hash==$signature){
            return true;
        }

        return false;
     }

     /**
      * Check if pending order exists 
      * @param - $subscription - WC_Subscription object
      * @return - Array of pending order_id/s of pending order or false
      */
      function get_pending_orders($subscription){
          $related_orders=$subscription->get_related_orders();
          $pending_orders=array();
          foreach($related_orders as $order_id){
              $order_status=wc_get_order($order_id)->get_status();
              if($order_status=='pending'){
                  $pending_orders[]=$order_id;
              }
              
          }
          return $pending_orders;
      }

      /**
       * function to create hash
       * @param - $payload
       * @return - string(sha-256 hash)
       */

       function get_hash($payload){
           $secret_key=get_option('qlm_webhook_secret_key');
           $secret_key=$secret_key?$secret_key:"";
           $secret_payload=$payload.$secret_key;
        //    print_r($secret_payload);
        //    echo ",";
           $hash=hash('sha256', $secret_payload);
           return $hash;
       }

       /**
        * This will trigger customer_completed_order email after listening to API
        * @param - $order_id
        */

       function trigger_mail($order_id){
        $mailer = WC()->mailer();
        $mails = $mailer->get_emails();
        if ( ! empty( $mails ) ) {
            foreach ( $mails as $mail ) {
                if ( $mail->id == 'customer_renewal_invoice' ) {
                   $mail->trigger( $order_id );
                }
             }
        }
       }
?>