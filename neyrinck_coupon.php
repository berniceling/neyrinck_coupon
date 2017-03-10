<?php
    /*
    Plugin Name: Neyrinck Coupon
    Description: This plugin generates 1 year free VCP Bundle coupon that ties with Spill user's ilok id.  
    Author: Bernice Ling
    Version: 1.0
    */

    // activate the process when payment is completed
    add_action( 'woocommerce_order_status_completed', 'bl_process_coupon', 10, 1 );

    function bl_test(){
        return "THIS IS A TEST FROM neyrinck_coupon.php";
    }
    
    function bl_process_coupon($order_id ) {
        // $is_coupon_required = bl_is_coupon_required($order_id);
        // if (!$is_coupon_required) return $order_id; 
        $ilok_user_id = bl_get_ilok_id($order_id);
        if (!$ilok_user_id) return $order_id; 

        $order = new WC_Order( $order_id );
        foreach($order->get_items() as $item) {
            $product_name = $item['name'];
            if ($product_name == "Spill Plug-In"){
                $coupon_code = bl_generate_coupon($ilok_user_id, $order_id);
                bl_send_coupon($ilok_user_id, $coupon_code, $order_id);
            }
        }

       
    }

    function bl_send_coupon($ilok_user_id, $coupon_code, $order_id){

        if (is_array($order_id)){
            $customer = $order_id;
        } else {
            $customer = bl_customer_info($order_id);
        }

        
        $firstname = $customer['firstname'];
        $lastname = $customer['lastname'];
        $email = $customer['email'];

        $sender = "store@neyrinck.com";
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= "From: Neyrinck Store<$sender>" . "\r\n";
        $subject = 'V-Control Pro Bundle Coupon';
              
        ob_start();
        include 'coupon.html';
        $email_content = ob_get_clean();
        mail( $email, $subject, $email_content,$headers );

    }

    function bl_generate_coupon_content($ilok_user_id, $coupon_code, $order_id){
        if (is_array($order_id)){
            $customer = $order_id;
        } else {
            $customer = bl_customer_info($order_id);
        }

        if (is_array($coupon_code)){
            $codes = '';
           // foreach ($coupon_code as $code){
            for ($i = 0; $i < count($coupon_code); $i++) {
                $codes .= $coupon_code[$i];
                if ($i < count($coupon_code)-1)
                    $codes .= ", ";
            }

            $coupon_code = $codes;
        }
        
        $firstname = $customer['firstname'];
        $lastname = $customer['lastname'];
        $email = $customer['email'];
        
        ob_start();
        include 'coupon_content.html';
        $content = ob_get_clean();
        
        return $content;

    }

    function bl_customer_info($order_id){
        global $wpdb;
        $customer = array();
        $result= $wpdb->get_results( 'SELECT * FROM wp_postmeta WHERE post_id = '.$order_id, OBJECT );
        foreach($result as $res) {
            if( $res->meta_key == '_billing_first_name'){
                   $customer['firstname'] = $res->meta_value;   
            }
            if( $res->meta_key == '_billing_last_name'){
                   $customer['lastname'] = $res->meta_value;   
            }
            if( $res->meta_key == '_billing_email'){
                   $customer['email'] = $res->meta_value;  
            }

        }

        return $customer;
    }

    function bl_is_coupon_required($order_id){
        $product_name = "Spill Plug-In";
        $order_item_id = bl_get_order_item_id_by_product_name($product_name, $order_id);
        global $wpdb;
        $results= $wpdb->get_results( 'SELECT meta_value FROM wp_woocommerce_order_itemmeta WHERE meta_key = "Free V-Control Pro Bundle" AND order_item_id = '.$order_item_id, OBJECT );
        $response = $results[0]->meta_value;
        if (stripos($response, 'yes') !== false || stripos($response, 'please') !== false) return true;
        else return false;
    }


    function bl_get_order_item_id_by_product_name($product_name, $order_id){
        global $wpdb;
        $order_item_ids = $wpdb->get_results( 'SELECT order_item_id FROM wp_woocommerce_order_items WHERE order_item_name ="'.$product_name.'" AND order_id= '.$order_id, OBJECT );
        if ($order_item_ids) {
            $item_id = $order_item_ids[0]->order_item_id;
        } 
        return $item_id;
    }

 
    function bl_get_order_item_id($order_id){
        global $wpdb;
        $results = $wpdb->get_results( 'SELECT order_item_id FROM wp_woocommerce_order_items WHERE order_id= '.$order_id, OBJECT );
        $order_item_id = $results[0]->order_item_id;
        return $order_item_id;
    }

    function bl_get_ilok_id($order_id){
        $order_item_id = bl_get_order_item_id($order_id);
        global $wpdb;
        $results = $wpdb->get_results( 'SELECT meta_value FROM wp_woocommerce_order_itemmeta WHERE meta_key LIKE "%iLok%"  AND order_item_id= '.$order_item_id, OBJECT );
        $msg = $wpdb->last_query;
        $ilok_user_id = $results[0]->meta_value;
        return $ilok_user_id;
    }

    function bl_mail($msg, $email){
        $from = "store@neyrinck.com";
        $subject = "DEBUG Status : NEYRINCK COUPON"; 
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= "From: Neyrinck Store<$from>" . "\r\n";
        if (!$email)$email = "berniceling@yahoo.com";
        
        mail($email, $subject, $msg, $headers);

    }


    function bl_get_coupon_by_order_id($order_id){
        global $wpdb;
        $sql = "SELECT post_id FROM  `wp_postmeta` WHERE  `meta_key` LIKE  'order_id' AND `meta_value` LIKE $order_id";
        $results= $wpdb->get_results( $sql, OBJECT );
        $coupon_id = $results[0]->post_id;
        
        // get ilok id
        $sql = "SELECT meta_value FROM  `wp_postmeta` WHERE  `post_id` =  $coupon_id AND `meta_key` LIKE 'customer_ilok_id'";
        $results= $wpdb->get_results( $sql, OBJECT );
        $ilokID = $results[0]->meta_value;
       // return $ilokID;

        // get coupon code
        $sql = "SELECT post_title FROM  `wp_posts` WHERE  `ID` =$coupon_id";
        $results= $wpdb->get_results( $sql, OBJECT );
        $coupon_code = $results[0]->post_title;
        $coupons = array();

        foreach( $results as $result ) {
            $coupons[] = $result->post_title;
        } 
       
        $response['ilok'] = $ilokID;
        $response['coupon'] = $coupons;
        return $response;
    }


   
    function bl_generate_coupon($ilok_user_id, $order_id){
        $oneYearOn = date('Y-m-d',strtotime(date("Y-m-d", time()) . " + 365 day"));
        $coupon_code = uniqid(); // Code
        $amount = '49.99'; // Amount
        $discount_type = 'fixed_product'; // Type: fixed_cart, percent, fixed_product, percent_product
        $now = date("Y-m-d H:i:s");
                            
        $coupon = array(
            'post_title' => $coupon_code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type'   => 'shop_coupon',
            'post_name'   => 'VCP Coupon',
            'post_date'   => $now,

        );

        global $wpdb;
        $wpdb->insert('wp_posts', $coupon);
        $new_coupon_id = $wpdb->insert_id;
       
        // Add meta
        bl_update_coupon_meta( $new_coupon_id, 'customer_ilok_id', $ilok_user_id);
        bl_update_coupon_meta( $new_coupon_id, 'discount_type', $discount_type );
        bl_update_coupon_meta( $new_coupon_id, 'expiry_date', $oneYearOn);
        bl_update_coupon_meta( $new_coupon_id, 'coupon_amount', $amount );
        bl_update_coupon_meta( $new_coupon_id, 'individual_use', 'yes' );
        bl_update_coupon_meta( $new_coupon_id, 'product_ids', '51410' );
        bl_update_coupon_meta( $new_coupon_id, 'order_id', $order_id );
        bl_update_coupon_meta( $new_coupon_id, 'usage_limit', '1' );
        bl_update_coupon_meta( $new_coupon_id, 'free_shipping', 'no' );

        return $coupon_code;
 }

    function bl_update_coupon_meta ($post_id, $key, $value){
      global $wpdb;
      $meta_update = array(
           'meta_key' => $key,    
            'meta_value' => $value,
            'post_id' => $post_id

      );
     
      $wpdb->insert( 'wp_postmeta', $meta_update);
      $msg = $wpdb->last_query;
      $wpdb->flush();

    }

   
?>
