<?php

class WC_Gateway_Flitt_Card extends WC_Flitt_Payment_Gateway
{
    use Flitt_Embedded;
    use Flitt_Hosted;
    use Flitt_Seamless;

    /**
     * @var WC_Flitt_Subscriptions_Compat
     */
    private $subscriptions;
    /**
     * @var WC_Flitt_Pre_Orders_Compat
     */
    private $pre_orders;

    public function __construct()
    {
        $this->id = 'flitt'; // payment gateway plugin ID
        $this->icon = plugins_url('assets/img/logo.svg', WC_FLITT_BASE_FILE); // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = false; // in case you need a custom credit card form
        $this->method_title = 'Flitt';
        $this->method_description = __('Card payments, Apple/Google Pay', 'flitt-woocommerce-payment-gateway');

        $this->supports = [
            'products',
            'refunds',
            'pre-orders',
            'subscriptions',
            'subscription_reactivation',
            'subscription_cancellation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_suspension'
        ];

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->test_mode = 'yes' === $this->get_option('test_mode');
        $this->merchant_id = (int)$this->get_option('merchant_id');
        $this->secret_key = $this->get_option('secret_key');
        $this->integration_type = $this->get_option('integration_type') ? $this->get_option('integration_type') : false;
        $this->redirect_page_id = $this->get_option('redirect_page_id');
        $this->completed_order_status = $this->get_option('completed_order_status') ? $this->get_option('completed_order_status') : false;
        $this->expired_order_status = $this->get_option('expired_order_status') ? $this->get_option('expired_order_status') : false;
        $this->declined_order_status = $this->get_option('declined_order_status') ? $this->get_option('declined_order_status') : false;

        parent::__construct();

        if (class_exists('WC_Pre_Orders_Order')) {
            $this->pre_orders = new WC_Flitt_Pre_Orders_Compat($this);
        }

        if (class_exists('WC_Subscriptions_Order')) {
            $this->subscriptions = new WC_Flitt_Subscriptions_Compat($this);
        }
    }

    /**
     * action hook to add setting payment page Pre-Orders notice
     */
    public function admin_options()
    {
        do_action('wc_gateway_flitt_admin_options');
        parent::admin_options();
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'flitt-woocommerce-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Flitt Gateway', 'flitt-woocommerce-payment-gateway'),
                'default' => 'no',
                'description' => __('Show in the Payment List as a payment option', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
            'test_mode' => [
                'title' => __('Test mode', 'flitt-woocommerce-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Test Mode', 'flitt-woocommerce-payment-gateway'),
                'default' => 'no',
                'description' => __('Place the payment gateway in test mode using test Merchant ID', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
            'title' => [
                'title' => __('Title', 'flitt-woocommerce-payment-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout', 'flitt-woocommerce-payment-gateway'),
                'default' => __('Flitt Cards, Apple/Google Pay', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description:', 'flitt-woocommerce-payment-gateway'),
                'type' => 'textarea',
                'default' => __('Pay securely by Credit/Debit Card or by Apple/Google Pay with Flitt.', 'flitt-woocommerce-payment-gateway'),
                'description' => __('This controls the description which the user sees during checkout', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
            'merchant_id' => [
                'title' => __('Merchant ID', 'flitt-woocommerce-payment-gateway'),
                'type' => 'text',
                'description' => __('Given to Merchant by Flitt', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
            'secret_key' => [
                'title' => __('Secret Key', 'flitt-woocommerce-payment-gateway'),
                'type' => 'text',
                'description' => __('Given to Merchant by Flitt', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
            'integration_type' => [
                'title' => __('Payment integration type', 'flitt-woocommerce-payment-gateway'),
                'type' => 'select',
                'options' => $this->getIntegrationTypes(),
                'description' => __('How the payment form will be displayed', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
            'redirect_page_id' => [
                'title' => __('Return Page', 'flitt-woocommerce-payment-gateway'),
                'type' => 'select',
                'options' => $this->flitt_get_pages(__('Default order page', 'flitt-woocommerce-payment-gateway')),
                'description' => __('URL of success page', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
            'completed_order_status' => [
                'title' => __('Payment completed order status', 'flitt-woocommerce-payment-gateway'),
                'type' => 'select',
                'options' => $this->getPaymentOrderStatuses(),
                'default' => 'none',
                'description' => __('The completed order status after successful payment', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
            'expired_order_status' => [
                'title' => __('Payment expired order status', 'flitt-woocommerce-payment-gateway'),
                'type' => 'select',
                'options' => $this->getPaymentOrderStatuses(),
                'default' => 'none',
                'description' => __('Order status when payment was expired', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
            'declined_order_status' => [
                'title' => __('Payment declined order status', 'flitt-woocommerce-payment-gateway'),
                'type' => 'select',
                'options' => $this->getPaymentOrderStatuses(),
                'default' => 'none',
                'description' => __('Order status when payment was declined', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
        ];
    }

    public function flittPaymentComplete($order, $transactionID)
    {
        if ($this->pre_orders && WC_Pre_Orders_Order::order_contains_pre_order($order)) {
            $order->set_transaction_id($transactionID);
            WC_Pre_Orders_Order::mark_order_as_pre_ordered($order);
        } else parent::flittPaymentComplete($order, $transactionID);
    }

    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (empty($order))
            return false;

        try {
            $reverse = WC_Flitt_API::reverse([
                'order_id' => $this->getFlittOrderID($order),
                'amount' => (int)round($amount * 100),
                'currency' => $order->get_currency(),
                'comment' => substr($reason, 0, 1024)
            ]);

            switch ($reverse->reverse_status) {
                case 'approved':
                    return true;
                case 'processing':
                    /* translators: 1) reverse status */
                    $order->add_order_note(sprintf(__('Refund Flitt status: %1$s', 'flitt-woocommerce-payment-gateway'), $reverse->reverse_status));
                    return true;
                case 'declined':
                    $noteText = sprintf(__('Refund Flitt status: %1$s', 'flitt-woocommerce-payment-gateway'), $reverse->reverse_status);
                    $order->add_order_note($noteText);
                    throw new Exception($noteText);
                default:
                    $noteText = sprintf(__('Refund Flitt status: %1$s', 'flitt-woocommerce-payment-gateway'), 'Unknown');
                    $order->add_order_note($noteText);
                    throw new Exception($noteText);
            }
        } catch (Exception $e) {
            return new WP_Error('error', $e->getMessage());
        }
    }

    public function getPaymentOptions()
    {
        $paymentOptions = parent::getPaymentOptions();

        $paymentOptions['methods'] = ['card', 'wallets'];
        $paymentOptions['methods_disabled'] = ['banklinks_eu', 'local_methods'];
        $paymentOptions['active_tab'] = 'card';

        return $paymentOptions;
    }

    /**
     * what can be seen on the Checkout page in the choice of payment method.
     * mb use later in seamless integration
     */
//    public function payment_fields()
//    {
//        echo wpautop(wptexturize($this->description));
//    }

}
