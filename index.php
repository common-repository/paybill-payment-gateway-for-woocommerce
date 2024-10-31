<?php

/*
    Plugin Name: PayBill.NG Payment Gateway Plugin for WooCommerce
    Plugin URI: http://paybill.ng
    Description: Woocommerce payment gateway plugin for PayBill.NG
    Version: 0.1.7
    Author: PayBill.NG Team
    Author URI: http://paybill.ng/
    Copyright: © 2017 http://paybill.ng
    License: GNU General Public License v3.0
    License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

defined( 'ABSPATH' ) or exit;

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}


function pay_bill_ng_processing_form_custom_redirect()
{
    if( is_page('Processing-PaybillNG'))
    {
        $data_ref = '';

        $data_order_id = '';

        $ref = filter_var($_GET['ref'], FILTER_SANITIZE_STRING);

        $order_id = filter_var($_GET['id'], FILTER_SANITIZE_STRING);


        if(filter_var($ref, FILTER_SANITIZE_STRING) == '' || filter_var($ref, FILTER_SANITIZE_STRING) == null || !filter_var($ref, FILTER_SANITIZE_STRING)) {

            wp_safe_redirect(home_url());

        } else {
            $data_ref = esc_attr($ref);
        }


        if(filter_var($order_id, FILTER_SANITIZE_STRING) == '' || filter_var($order_id, FILTER_SANITIZE_STRING) == null || !filter_var($order_id, FILTER_SANITIZE_STRING)) {

            wp_safe_redirect(home_url());

        } else {
            $data_order_id = esc_attr($data_order_id);
        }

        if($data_ref == null & $data_order_id == null) {
            wp_safe_redirect(home_url());
            exit();
        }

        include 'includes/templates/processing_form.php';
    }
}

add_action( 'template_redirect', 'pay_bill_ng_processing_form_custom_redirect' );

add_action('plugins_loaded', 'paybillng_init_paybill_gateway_class', 0);

function paybillng_init_paybill_gateway_class()
{

    class WC_PayBill_NG_Gateway extends WC_Payment_Gateway
    {

        private $liveurlonly;
        private $sandbox;
        private $hidepaybilllogo;
        private $test_public_key;
        private $test_secret_key;
        private $organisation_code;
        private $confirm_live_payment;
        private $user_authorization_token;
        private $public_key;
        private $live_public_key;
        private $sub_account_code;
        private $organisation_transaction_charge;
        private $payment_charge_bearer;

        public function __construct()
        {

            $this->id = 'paybillng';
            $this->has_fields = true;
            $this->method_title = "PayBill.ng Payment Gateway";
            $this->method_description = "PayBill.NG Payment Gateway, Fast, Simple, Easy";
            $this->icon = plugins_url('images/logo.png', __FILE__);
            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->organisation_code = $this->settings['organisation_code'];
            $this->user_authorization_token = $this->settings['user_authorization_token'];
            $this->test_public_key = $this->settings['test_public_key'];
            $this->test_secret_key = $this->settings['test_secret_key'];
            $this->live_public_key = $this->settings['live_public_key'];
            $this->live_secret_key = $this->settings['live_secret_key'];
            $this->sandbox = $this->settings['sandbox'];
            $this->hidepaybilllogo = $this->settings['hidepaybilllogo'];
            $this->sub_account_code = $this->settings['sub_account_code'];
            $this->organisation_transaction_charge = $this->settings['organisation_transaction_charge'];
            $this->payment_charge_bearer = $this->settings['payment_charge_bearer'];


            if ($this->hidepaybilllogo == 'yes') {
                $this->icon = '';
            }

            $this->liveurlonly = "https://paybill.ng/assets/paynou/js/v1/paynou.inline.min.js";

            $this->confirm_live_payment = "https://paybill.ng/api/paynou/transaction/status/";


            if ($this->sandbox == 'yes') {
                $this->public_key = $this->test_public_key;
            } else {
                $this->public_key = $this->live_public_key;
            }

            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }

            add_action('woocommerce_receipt_paybillng', array($this, 'paybillng_order_pay'));

        }


        function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'paybills'),
                    'type' => 'checkbox',
                    'label' => __('Enable Paybill Online Payment Gateway', 'paybills'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'paybills'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'paybills'),
                    'default' => __('PayBill.NG', 'paybills'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'paybills'),
                    'type' => 'textarea',
                    'default' => 'Pay securely by Debit/Credit Card or Bank Account through PayBill.NG Payment Gateway.'
                ),
                'sandbox' => array(
                    'title' => __('Enable Sandbox?', 'paybills'),
                    'type' => 'checkbox',
                    'label' => __('Enable Sandbox PayBill.NG Payment.', 'paybills'),
                    'default' => 'no'),

                'hidepaybilllogo' => array(
                    'title' => __('Show/Hide Logo', 'paybills'),
                    'type' => 'checkbox',
                    'label' => __('Hide Paybill logo on checkout page.', 'paybills'),
                    'default' => 'no'),

                'organisation_code' => array(
                    'title' => __('Organisation Code', 'paybills'),
                    'type' => 'text',
                    'description' => __('Given to Merchant by PayBill.NG found directly below company name once user log\'s in  ', 'paybills')),

                'test_public_key' => array(
                    'title' => __('Test Public Key', 'paybills'),
                    'type' => 'text',
                    'description' => __('Given to Merchant by PayBill.NG this is found in settings', 'paybills'),
                ),

                'test_secret_key' => array(
                    'title' => __('Test Secret Key', 'paybills'),
                    'type' => 'text',
                    'description' => __('Given to Merchant by PayBill.NG this is found in settings', 'paybills'),
                ),

                'live_public_key' => array(
                    'title' => __('Live Public Key', 'paybills'),
                    'type' => 'text',
                    'description' => __('Given to Merchant by PayBill.NG this is found in settings', 'paybills'),
                ),

                'live_secret_key' => array(
                    'title' => __('Live Secret Key', 'paybills'),
                    'type' => 'text',
                    'description' => __('Given to Merchant by PayBill.NG this is found in settings', 'paybills'),
                ),

                'sub_account_code' => array(
                    'title' => __('Sub Account Code', 'paybills'),
                    'type' => 'text',
                    'description' => __('Given to Merchant by PayBill.NG this is found in settings', 'paybills'),
                ),

                'organisation_transaction_charge' => array(
                    'title' => __('Organisation Transaction Charge', 'paybills'),
                    'type' => 'number',
                    'description' => __('Given to Merchant by PayBill.NG this is found in settings', 'paybills'),
                ),

                'payment_charge_bearer' => array(
                    'title' => __('Payment Charge Bearer', 'paybills'),
                    'type' => 'text',
                    'default' => _('sub_account')
                )

            );
        }

        function admin_options()
        {
            ?>
            <h2><?php _e('Paybill.ng Payment Gateway', 'woocommerce'); ?></h2>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table> <?php
        }

        public function paybillng_order_pay($order)
        {
            echo '<p>' . __('Thank you for your order, please click the button below to pay with PayBill.NG.') . '</p>';
            echo "<button id='paybill_iframe_launch' class='btn'>Pay</button>";
            echo $this->generate_paybill_form($order);
        }

        function process_payment($order_id)
        {
            $order = new WC_Order($order_id);
            update_post_meta($order_id, '_post_data', $_POST);
            return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url(true));
        }


        public function generate_paybill_form($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);
            $order_id = $order_id . '_' . date("ymds");

            $post_data = get_post_meta($order_id, '_post_data', true);
            update_post_meta($order_id, '_post_data', array());

            $email = $order->billing_email;

            $the_order_total = $order->order_total;

            $form = '';

            echo "<script src='$this->liveurlonly'></script>";

            ?>

            <script type="text/javascript">

                jQuery('#paybill_iframe_launch').click(function () {

                        PayBillService.load({
                            'customer_email': '<?= $email ?>',
                            'amount': <?= $the_order_total ?>,
                            'organization_code': '<?= $this->organisation_code ?>',
                            'organization_unique_reference': '<?= $order_id ?>',
                            'organization_public_key': '<?= $this->public_key ?>',
                            'sub_account_code': '<?= $this->sub_account_code ?>',
                            'currency':'NGN',
                            'organization_transaction_charge': '<?= $this->organisation_transaction_charge ?>',
                            'payment_charge_bearer': '<?= $this->payment_charge_bearer ?>',
                            'onClose': function (ref) {
                                window.location.href = "<?= esc_url( get_permalink( get_page_by_title('Processing-PaybillNG') ) ); ?>&id=<?= $order->get_id()?>&ref="+ ref;
                            }
                        });
                    });

            </script>

            <?php

            return $form;
        }


        }

        function add_paybill_gateway_class( $methods ) {
            $methods[] = 'WC_PayBill_NG_Gateway';
            return $methods;
        }

        add_filter( 'woocommerce_payment_gateways', 'add_paybill_gateway_class' );

}

    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'paybill_payment_gateway_action_links' );
    function paybill_payment_gateway_action_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paybillng' ) . '">' . __( 'Settings', 'paybill_gateway' ) . '</a>',
        );
        return array_merge( $plugin_links, $links );
    }

    function paybillng_plugin_activated()
    {
        include 'includes/paybillng_activate_plugin.php';
        paybillng_activate_plugin::plugin_activated();
    }


    function paybillng_plugin_deactivated()
    {
        include 'includes/paybillng_deactivate_plugin.php';
        paybillng_deactivate_plugin::plugin_deactivated();
    }

    register_activation_hook( __FILE__, 'paybillng_plugin_activated');

    register_deactivation_hook( __FILE__, 'paybillng_plugin_deactivated');
