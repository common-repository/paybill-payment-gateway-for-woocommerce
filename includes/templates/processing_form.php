<?php
/**
 * Created by PhpStorm.
 * User: kobi
 * Date: 21/04/2017
 * Time: 9:17 AM
*/
    class paybillng_processing_form extends WC_Payment_Gateway
    {
        function paybillng_verifyId() {

            global $woocommerce;

            $msg['class']   = 'error';
            $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";

            $settings_array = WC_Admin_Settings::get_option( 'woocommerce_paybillng_settings');

            $sandbox = $settings_array['sandbox'];

            $data = '';

            $order_id = '';

            $data_ref = filter_var($_GET['ref'], FILTER_SANITIZE_STRING);

            $data_order_id = filter_var($_GET['id'], FILTER_SANITIZE_STRING);


            if(filter_var($data_ref, FILTER_SANITIZE_STRING) == '' || filter_var($data_ref, FILTER_SANITIZE_STRING) == null || !filter_var($data_ref, FILTER_SANITIZE_STRING)) {

                 wp_safe_redirect(home_url());

            } else {
               $data = esc_attr($data_ref);
            }


            if(filter_var($data_order_id, FILTER_SANITIZE_STRING) == '' || filter_var($data_order_id, FILTER_SANITIZE_STRING) == null || !filter_var($data_order_id, FILTER_SANITIZE_STRING)) {

                wp_safe_redirect(home_url());

            } else {
                $order_id = esc_attr($data_order_id);
            }


            try {

                $order = new WC_Order($order_id);

                if($sandbox == 'yes'){
                    $secret_key = $settings_array['test_secret_key'];

                }else{

                    $secret_key = $settings_array['live_secret_key'];
                }


                $url = "https://paybill.ng/api/paynou/transaction/status/";


                $args = array(
                    'timeout' => 200,
                    'httpversion' => '1.1',
                    'headers' => array(
                        'Authorization' => 'Bearer '.$secret_key)
                );

                $my_response = wp_remote_get($url.$data, $args);



                if (!empty($my_response)) {

                    $my_response = $my_response['body'];
                    $my_response = json_decode($my_response);



                    if ($my_response->data->status == "SUCCESSFUL") {
                        $order->add_order_note('Payment successful');
                        $order->payment_complete();
                        $order->reduce_order_stock();
                        $woocommerce->cart->empty_cart();
                        $order->get_order_number();
                        $order->get_formatted_order_total();
                        $order->get_payment_method_title();
                        $msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                        $msg['class'] = 'success';


                    }
                    elseif (($my_response->data->status === "error")||($my_response->data->status === "CANCELED")) {

                        $order->update_status("failed");
                        $order->add_order_note('Failed');
                        $msg['class'] = 'error';
                        $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                    }
                    else {

                        $order->update_status("failed");
                        $order->add_order_note('Failed');
                        $msg['class'] = 'error';
                        $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";

                    }
                }

            }
            catch (ErrorException $e)
            {
                $msg['class'] = 'error';
                $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";

            }

            if ( function_exists( 'wc_add_notice' ) )
            {
                wc_add_notice( $msg['message'], $msg['class'] );

            } else {

                if($msg['class']=='success'){
                    $woocommerce->add_message( $msg['message']);
                }else{
                    $woocommerce->add_error( $msg['message'] );

                }
                $woocommerce->set_messages();
            }

            $redirect_url = $this->get_return_url( $order );

            wp_safe_redirect( $redirect_url );
            exit;

        }
    }

$active = new paybillng_processing_form();
$active->paybillng_verifyId();