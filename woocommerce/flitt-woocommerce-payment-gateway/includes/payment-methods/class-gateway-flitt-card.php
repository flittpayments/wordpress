<?php

class Flitt_WC_Gateway_Card extends Flitt_WC_Payment_Gateway
{
    use Flitt_Embedded;
    use Flitt_Hosted;
    use Flitt_Seamless;

    /**
     * @var Flitt_WC_Subscriptions_Compat
     */
    private $subscriptions;
    /**
     * @var Flitt_WC_Pre_Orders_Compat
     */
    private $pre_orders;

    public function __construct()
    {
        $this->id = 'flitt'; // payment gateway plugin ID
        $this->icon = plugins_url('assets/img/logo.svg', FLITT_WC_BASE_FILE); // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = false; // in case you need a custom credit card form
        $this->method_title = 'Flitt';
        $this->method_description = __('Card payments, Apple/Google Pay', 'flitt-payment-gateway-for-woocommerce');

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
        $this->debug_mode = 'yes' === $this->get_option('logging');
        $this->flitt_merchant_id = (int)$this->get_option('flitt_merchant_id');
        $this->flitt_secret_key = $this->get_option('flitt_secret_key');
        $this->integration_type = $this->get_option('integration_type') ? $this->get_option('integration_type') : false;
        $this->redirect_page_id = $this->get_option('redirect_page_id');
        $this->completed_order_status = $this->get_option('completed_order_status') ? $this->get_option('completed_order_status') : false;
        $this->expired_order_status = $this->get_option('expired_order_status') ? $this->get_option('expired_order_status') : false;
        $this->declined_order_status = $this->get_option('declined_order_status') ? $this->get_option('declined_order_status') : false;

        if (class_exists('WC_Pre_Orders_Order')) {
            $this->pre_orders = new Flitt_WC_Pre_Orders_Compat($this);
        }

        if (class_exists('WC_Subscriptions_Order')) {
            $this->subscriptions = new Flitt_WC_Subscriptions_Compat($this);
        }

        parent::__construct();
    }

    /**
     * action hook to add setting payment page Pre-Orders notice
     */
    public function admin_options()
    {
        do_action('flitt_wc_gateway_admin_options');
        parent::admin_options();
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Flitt Gateway', 'flitt-payment-gateway-for-woocommerce'),
                'default' => 'no',
                'description' => __('Show in the Payment List as a payment option', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ],
            'test_mode' => [
                'title' => __('Test mode', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Test Mode', 'flitt-payment-gateway-for-woocommerce'),
                'default' => 'no',
                'description' => __('Place the payment gateway in test mode using test Merchant ID', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ],
            'logging' => [
                'title' => __('Debug Mode', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Debug Mode', 'flitt-payment-gateway-for-woocommerce'),
                'default' => 'no',
                'description' => __('Inject detailed debug metadata into all requests (with this behavior disabled in production).', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ],
            'title' => [
                'title' => __('Title', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout', 'flitt-payment-gateway-for-woocommerce'),
                'default' => __('Flitt Cards, Apple/Google Pay', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description:', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'textarea',
                'default' => __('Pay securely by Credit/Debit Card or by Apple/Google Pay with Flitt.', 'flitt-payment-gateway-for-woocommerce'),
                'description' => __('This controls the description which the user sees during checkout', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ],
            'flitt_merchant_id' => [
                'title' => __('Merchant ID', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'text',
                'description' => __('Given to Merchant by Flitt', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ],
            'flitt_secret_key' => [
                'title' => __('Secret Key', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'text',
                'description' => __('Given to Merchant by Flitt', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ],
            'integration_type' => [
                'title' => __('Payment integration type', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'select',
                'options' => $this->getIntegrationTypes(),
                'description' => __('How the payment form will be displayed', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ],
            'redirect_page_id' => [
                'title' => __('Return Page', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'select',
                'options' => $this->flitt_get_pages(__('Default order page', 'flitt-payment-gateway-for-woocommerce')),
                'description' => __('URL of success page', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ],
            'completed_order_status' => [
                'title' => __('Payment completed order status', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'select',
                'options' => $this->getPaymentOrderStatuses(),
                'default' => 'none',
                'description' => __('The completed order status after successful payment', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ],
            'expired_order_status' => [
                'title' => __('Payment expired order status', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'select',
                'options' => $this->getPaymentOrderStatuses(),
                'default' => 'none',
                'description' => __('Order status when payment was expired', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ],
            'declined_order_status' => [
                'title' => __('Payment declined order status', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'select',
                'options' => $this->getPaymentOrderStatuses(),
                'default' => 'none',
                'description' => __('Order status when payment was declined', 'flitt-payment-gateway-for-woocommerce'),
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
        $this->set_params();

        if (empty($order))
            return false;

        try {
            $reverse = $this->reverse([
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
                    $order->add_order_note(sprintf(__('Refund Flitt status: %1$s', 'flitt-payment-gateway-for-woocommerce'), $reverse->reverse_status));
                    return true;
                case 'declined':
                    /* translators: reverse flitt status */
                    $noteText = sprintf(__('Refund Flitt status: %1$s', 'flitt-payment-gateway-for-woocommerce'), $reverse->reverse_status);
                    $order->add_order_note($noteText);
                    throw new Exception($noteText);
                default:
                    /* translators: unknown flitt status */
                    $noteText = sprintf(__('Refund Flitt status: %1$s', 'flitt-payment-gateway-for-woocommerce'), 'Unknown');
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
