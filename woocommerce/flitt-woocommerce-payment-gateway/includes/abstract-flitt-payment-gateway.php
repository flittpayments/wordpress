<?php

if (!defined('ABSPATH')) {
    exit;
}

class Flitt_WC_Payment_Gateway extends WC_Payment_Gateway
{
    const API_URL = 'https://pay.flitt.com/api/';
    const TEST_MERCHANT_ID = 1549901;
    const TEST_MERCHANT_SECRET_KEY = 'test';

    const ORDER_APPROVED = 'approved';
    const ORDER_DECLINED = 'declined';
    const ORDER_EXPIRED = 'expired';
    const ORDER_PROCESSING = 'processing';
    const ORDER_CREATED = 'created';
    const ORDER_REVERSED = 'reversed';
    const ORDER_SEPARATOR = "_";
    const META_NAME_FLITT_ORDER_ID = '_flitt_order_id';

    /**
     * @var string
     */
    protected $api_url = self::API_URL;

    public $test_mode;
    public $debug_mode;
    public $flitt_merchant_id;
    public $flitt_secret_key;
    public $integration_type;
    public $completed_order_status;
    public $expired_order_status;
    public $declined_order_status;
    public $redirect_page_id;

    /**
     * Flitt_WC_Payment_Gateway constructor.
     */
    public function __construct()
    {
        if ($this->test_mode) {
            $this->flitt_merchant_id = self::TEST_MERCHANT_ID;
            $this->flitt_secret_key = self::TEST_MERCHANT_SECRET_KEY;
        }

        $this->set_params();

        // callback handler
        add_action('woocommerce_api_' . strtolower(get_class($this)), [$this, 'callbackHandler']);

        // todo mb thankyoupage change order status or clear cart
        // add_action('woocommerce_before_thankyou', [$this, '']);

        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        if ($this->integration_type === 'embedded') {
            add_action('wp_enqueue_scripts', [$this, 'includeEmbeddedAssets']);
            add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        }

        if ($this->integration_type === 'seamless') {
            add_action('wp_enqueue_scripts', [$this, 'includeSeamlessAssets']);
            add_filter('woocommerce_order_button_html', [$this, 'custom_order_button_html']);
            add_action('wp_ajax_nopriv_flitt_generate_ajax_order_info', [$this, 'flitt_generate_ajax_order_info'], 99);
            add_action('wp_ajax_flitt_generate_ajax_order_info', [$this, 'flitt_generate_ajax_order_info'], 99);
        }
    }


    /**
     * setup merchant id and secret key
     */
    public function set_params()
    {
        if ( empty($this->id) ) {
            $this->id = 'flitt';
        }
        if ( empty($this->form_fields) ) {
            $this->init_form_fields();
        }
        if ( ! is_array( $this->settings ) || empty( $this->settings ) ) {
            $this->init_settings();
        }
        if ( !$this->flitt_merchant_id ) {
            $this->flitt_merchant_id = (int)$this->get_option('flitt_merchant_id');
        }
        if ( !$this->flitt_secret_key ) {
            $this->flitt_secret_key = $this->get_option('flitt_secret_key');
        }
        $this->debug_mode = 'yes' === $this->get_option('logging');
    }

    /**
     * @throws Exception
     */
    public function getCheckoutUrl($requestData)
    {
        $response = $this->sendToAPI('checkout/url', $requestData);

        return $response->checkout_url;
    }

    /**
     * @throws Exception
     */
    public function requestCheckoutToken($requestData)
    {
        $response = $this->sendToAPI('checkout/token', $requestData);

        return $response->token;
    }

    /**
     * @throws Exception
     */
    public function reverse($requestData)
    {
        return $this->sendToAPI('reverse/order_id', $requestData);
    }

    /**
     * @throws Exception
     */
    public function capture($requestData)
    {
        return $this->sendToAPI('capture/order_id', $requestData);
    }

    /**
     * @throws Exception
     */
    public function recurring($requestData)
    {
        return $this->sendToAPI('recurring', $requestData);
    }

    /**
     * @param $endpoint
     * @param $requestData
     * @return mixed
     * @throws Exception
     */
    protected function sendToAPI($endpoint, $requestData)
    {
        if (empty($this->flitt_merchant_id) || empty($this->flitt_secret_key)) {
            throw new Exception(esc_html__('Flitt merchant credentials are missing.', 'flitt-payment-gateway-for-woocommerce'));
        }
        $requestData['merchant_id'] = $this->flitt_merchant_id;

        $debug_data = null;
        if ($this->debug_mode) {
            try{
                $debug_data = $this->getDebugData($requestData);
            } catch (Exception $e) {
                $debug_data = $e->getMessage();
            }
        }

        $requestData['signature'] = $this->getSignature($requestData, $this->flitt_secret_key);

        if ($debug_data) {
            $requestData['debug_data'] = $debug_data;
        }

        $response = wp_safe_remote_post(
            $this->api_url . $endpoint,
            [
                'headers' => ["Content-type" => "application/json;charset=UTF-8"],
                'body' => json_encode(['request' => $requestData]),
                'timeout' => 70,
            ]
        );

        if (is_wp_error($response))
            throw new Exception(esc_html($response->get_error_message()));

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code != 200)
            throw new Exception(esc_html("Flitt API Return code is $response_code. Please try again later."));

        $result = json_decode($response['body']);

        if (empty($result->response) && empty($result->response->response_status))
            throw new Exception('Unknown Flitt API answer.');

        if ($result->response->response_status != 'success')
            throw new Exception(esc_html($result->response->error_message));

        return $result->response;
    }
    /**
     * Generate Debug Data
     */
    protected function getDebugData($requestData)
    {
        $signatureString = '';
        if (!empty($this->flitt_secret_key)) {
            $signatureString = $this->getSignature($requestData, $this->flitt_secret_key, false);
        }

        $maskedSecretKey = $this->maskSecretKey($this->flitt_secret_key);
        $maskedSignature = $signatureString;

        if (!empty($maskedSecretKey)) {
            $maskedSignature = str_replace($this->flitt_secret_key, $maskedSecretKey, $signatureString);
        }
        // Php extensions
        $extensions = get_loaded_extensions();
        sort($extensions);
        // Plugin extensions
        $options = $this->settings;
        unset(
            $options['flitt_secret_key']
        );
        if (isset($options['secret_key'])) {
            unset($options['secret_key']);
        }
        // All wp plugins
        try {
            $all_plugins = get_plugins();
            $plugins_array = [];
            foreach ( $all_plugins as $plugin_file => $plugin_data ) {
                $plugins_array[] = [
                    'name'    => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                ];
            }
        } catch (Exception $e) {
            $plugins_array = $e->getMessage();
        }

        $debugData = [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'php_extensions' => $extensions,
            'signature_string' => $maskedSignature,
            'plugin_options' => $options,
            'wordpress_plugins' => $plugins_array
        ];

        return $debugData;
    }

    /**
     * @param $data
     * @param $password
     * @param bool $encoded
     * @return mixed|string
     */
    protected function getSignature($data, $password, $encoded = true)
    {
        $data = array_filter($data, function ($var) {
            return $var !== '' && $var !== null;
        });
        ksort($data);

        $str = $password;
        foreach ($data as $k => $v) {
            $str .= '|' . $v;
        }

        return $encoded ? sha1($str) : $str;
    }

    /**
     * Mask secret key while keeping first and last characters visible.
     *
     * @param string $secretKey
     * @return string
     */
    protected function maskSecretKey($secretKey)
    {
        if (empty($secretKey)) {
            return '';
        }

        $length = strlen($secretKey);

        if ($length <= 2) {
            return str_repeat('*', $length);
        }

        if ($length <= 6) {
            return substr($secretKey, 0, 1) . str_repeat('*', $length - 2) . substr($secretKey, -1);
        }

        return substr($secretKey, 0, 3) . str_repeat('*', $length - 6) . substr($secretKey, -3);
    }

    /**
     * Process Payment.
     * Run after submit order button.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $this->set_params();
        $processResult = ['result' => 'success', 'redirect' => ''];

        try {
            if ($this->integration_type === 'embedded') {
                $processResult['redirect'] = $order->get_checkout_payment_url(true);
            } elseif ($this->integration_type === 'seamless') {
                $processResult['token'] = $this->getCheckoutToken($order);
            } else {
                $paymentParams = $this->getPaymentParams($order);
                $processResult['redirect'] = $this->getCheckoutUrl($paymentParams);
            }
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            $processResult['result'] = 'fail';
        }

        // in prev version we are use session to save redirect_url
        return apply_filters('flitt_wc_gateway_process_payment_complete', $processResult, $order);
    }

    /**
     * Flitt payment parameters
     *
     * @param WC_Order $order
     * @return mixed|void
     * @since 3.0.0
     */
    public function getPaymentParams($order)
    {
        $params = [
            'order_id' => $this->createFlittOrderID($order),
            'order_desc' => 'Order: ' . $order->get_id(),
            'amount' => (int)round($order->get_total() * 100),
            'currency' => get_woocommerce_currency(),
            'lang' => $this->getLanguage(),
            'sender_email' => $this->getEmail($order),
            'response_url' => $this->getResponseUrl($order),
            'server_callback_url' => $this->getCallbackUrl(),
            'reservation_data' => $this->getReservationData($order),
        ];

        return apply_filters('flitt_wc_gateway_flitt_payment_params', $params, $order);
    }

    /**
     * Generate unique flitt order id
     * and save it to order meta.
     *
     * @param $order
     * @return string
     * @since 3.0.0
     */
    public function createFlittOrderID($order)
    {
        $flittOrderID = $order->get_id() . self::ORDER_SEPARATOR . time();
        $order->update_meta_data(self::META_NAME_FLITT_ORDER_ID, $flittOrderID);
        $order->save();

        return $flittOrderID;
    }

    /**
     * Extracts flitt order if from order meta
     *
     * @param WC_Order $order
     * @return mixed
     * @since 3.0.0
     */
    public function getFlittOrderID($order)
    {
        return $order->get_meta(self::META_NAME_FLITT_ORDER_ID);
    }

    /**
     * Return custom or default order thank-you page url
     *
     * @param WC_Order $order
     * @return false|string|WP_Error
     * @since 3.0.0
     */
    public function getResponseUrl($order)
    {
        return $this->redirect_page_id ? get_permalink($this->redirect_page_id) : $this->get_return_url($order);
    }

    /**
     * Gets the transaction URL linked to Flitt merchant portal dashboard.
     *
     * @param WC_Order $order
     * @return string
     * @since 3.0.0
     */
    public function get_transaction_url($order)
    {
        $this->view_transaction_url = 'https://portal.flitt.com/mportal/#/payments/order/%s';
        return parent::get_transaction_url($order);
    }

    /**
     * get checkout token
     * cache it to session
     *
     * @param $order
     * @return array|string
     * @throws Exception
     */
    public function getCheckoutToken($order)
    {
        $this->set_params();
        $orderID = $order->get_id();
        $amount = (int)round($order->get_total() * 100);
        $currency = get_woocommerce_currency();
        $sessionTokenKey = 'session_token_' . md5($this->flitt_merchant_id . '_' . $orderID . '_' . $amount . '_' . $currency);
        $checkoutToken = WC()->session->get($sessionTokenKey);

        if (empty($checkoutToken)) {
            $paymentParams = $this->getPaymentParams($order);
            $checkoutToken = $this->requestCheckoutToken($paymentParams);
            WC()->session->set($sessionTokenKey, $checkoutToken);
        }

        return $checkoutToken;
    }

    /**
     * remove checkoutToken cache from session
     *
     * @param $paymentParams
     * @param $orderID
     */
    public function clearCache($paymentParams, $orderID)
    {
        WC()->session->__unset('session_token_' . md5($this->flitt_merchant_id . '_' . $orderID . '_' . $paymentParams['amount'] . '_' . $paymentParams['currency']));
    }

    /**
     * Flitt widget options
     *
     * @return array
     * @since 3.0.0
     */
    public function getPaymentOptions()
    {
        return [
            'full_screen' => false,
            'button' => true,
            'email' => true,
            'show_menu_first' => false,
        ];
    }

    /**
     * Site lang cropped
     *
     * @return string
     */
    public function getLanguage()
    {
        return substr(get_bloginfo('language'), 0, 2);
    }

    /**
     * Order Email
     *
     * @param WC_Order $order
     * @return string
     */
    public function getEmail($order)
    {
        $current_user = wp_get_current_user();
        $email = $current_user->user_email;

        if (empty($email)) {
            $order_data = $order->get_data();
            $email = $order_data['billing']['email'];
        }

        return $email;
    }

    public function getCallbackUrl()
    {
        return wc_get_endpoint_url('wc-api', strtolower(get_class($this)), get_site_url());
    }

    /**
     * Flitt antifraud parameters
     *
     * @param WC_Order $order
     * @return string
     * @since 3.0.0
     */
    public function getReservationData($order)
    {
        $orderData = $order->get_data();
        $orderDataBilling = $orderData['billing'];
        $referer = wp_get_referer();

        $reservationData = [
            'customer_zip' => $orderDataBilling['postcode'],
            'customer_name' => $orderDataBilling['first_name'] . ' ' . $orderDataBilling['last_name'],
            'customer_address' => $orderDataBilling['address_1'] . ' ' . $orderDataBilling['city'],
            'customer_state' => $orderDataBilling['state'],
            'customer_country' => $orderDataBilling['country'],
            'phonemobile' => $orderDataBilling['phone'],
            'account' => $orderDataBilling['email'],
            'cms_name' => 'Wordpress',
            'cms_version' => get_bloginfo('version'),
            'cms_plugin_version' => FLITT_WC_VERSION . ' (Woocommerce ' . WC_VERSION . ')',
            'shop_domain' => get_site_url(),
            'path' => $referer,
            'products' => $this->getReservationDataProducts($order->get_items())
        ];


        return base64_encode(json_encode($reservationData));
    }

    /**
     * data to create fiscal check
     *
     * @param $orderItemsProducts
     * @return array
     */
    public function getReservationDataProducts($orderItemsProducts)
    {
        $reservationDataProducts = [];

        try {
            /** @var WC_Order_Item_Product $orderProduct */
            foreach ($orderItemsProducts as $orderProduct) {
                $reservationDataProducts[] = [
                    'id' => $orderProduct->get_product_id(),
                    'name' => $orderProduct->get_name(),
                    'price' => $orderProduct->get_product()->get_price(),
                    'total_amount' => $orderProduct->get_total(),
                    'quantity' => $orderProduct->get_quantity(),
                ];
            }
        } catch (Exception $e) {
            $reservationDataProducts['error'] = $e->getMessage();
        }

        return $reservationDataProducts;
    }


    /**
     * @return array
     */
    public function getIntegrationTypes()
    {
        $integration_types = [];

        if (isset($this->embedded)) {
            $integration_types['embedded'] = __('Embedded', 'flitt-payment-gateway-for-woocommerce');
        }

        if (isset($this->hosted)) {
            $integration_types['hosted'] = __('Hosted', 'flitt-payment-gateway-for-woocommerce');
        }

        if (isset($this->seamless)) {
            $integration_types['seamless'] = __('Seamless (support only old checkout)', 'flitt-payment-gateway-for-woocommerce');
        }

        return $integration_types;
    }

    /**
     * @param bool $title
     * @param bool $indent
     * @return array
     */
    public function flitt_get_pages($title = false, $indent = true)
    {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) {
            $page_list[] = $title;
        }
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while ($has_parent) {
                    $prefix .= ' - ';
                    $next_page = get_post($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }

        return $page_list;
    }

    /**
     * Getting all available woocommerce order statuses
     *
     * @return array
     */
    public function getPaymentOrderStatuses()
    {
        $order_statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [];
        $statuses = [
            'default' => __('Default status', 'flitt-payment-gateway-for-woocommerce')
        ];
        if ($order_statuses) {
            foreach ($order_statuses as $k => $v) {
                $statuses[str_replace('wc-', '', $k)] = $v;
            }
        }
        return $statuses;
    }

    /**
     * Callback validation process
     *
     * @param $requestBody
     * @throws Exception
     */
    public function validateRequest($requestBody)
    {
        if (empty($requestBody)){
            throw new Exception('Empty request body.');
        }

        if ($this->flitt_merchant_id != $requestBody['merchant_id']){
            throw new Exception ('Merchant data is incorrect.');
        }

        $requestSignature = $requestBody['signature'];
        unset($requestBody['response_signature_string']);
        unset($requestBody['signature']);
        if ($requestSignature !== $this->getSignature($requestBody, $this->flitt_secret_key)) {
            throw new Exception ('Signature is not valid');
        }
    }

    /**
     * Flitt callback handler
     *
     * @since 3.0.0
     */
    public function callbackHandler()
    {
        try {
            $this->set_params();
            $requestBody = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
            $this->validateRequest($requestBody);

            if (!empty($requestBody['reversal_amount']) || $requestBody['tran_type'] === 'reverse'){
                // todo MB add refund complete note
                exit; // just ignore reverse callback
            }
            // order switch status process
            $orderID = strstr(sanitize_text_field($requestBody['order_id']), self::ORDER_SEPARATOR, true);
            $order = wc_get_order($orderID);
            $this->clearCache($requestBody, $orderID); // remove checkoutToken if exist

            do_action('flitt_wc_gateway_receive_valid_callback', $requestBody, $order);

            switch ($requestBody['order_status']) {
                case self::ORDER_APPROVED: //we recive with this status in 3 type transaction callback - purchase, capture and partial reverse
                    $this->flittPaymentComplete($order, (int)$requestBody['payment_id']);
                    break;
                case self::ORDER_CREATED:
                case self::ORDER_PROCESSING:
                    // we can receive processing status when Issuer bank declined payment. Mb add note.
                    // in default WC set pending status to order
                    break;
                case self::ORDER_DECLINED:
                    $newOrderStatus = $this->declined_order_status != 'default' ? $this->declined_order_status : 'failed';
                    /* translators: 1) flitt order status 2) flitt order id */
                    $orderNote = sprintf(__('Transaction ERROR: order %1$s<br/>Flitt ID: %2$s', 'flitt-payment-gateway-for-woocommerce'), sanitize_text_field($requestBody['order_status']), (int)$requestBody['payment_id']);
                    $order->update_status($newOrderStatus, $orderNote);
                    break;
                case self::ORDER_EXPIRED:
                    $newOrderStatus = $this->expired_order_status != 'default' ? $this->expired_order_status : 'cancelled';
                    /* translators: 1) flitt order status 2) flitt order id */
                    $orderNote = sprintf(__('Transaction ERROR: order %1$s<br/>Flitt ID: %2$s', 'flitt-payment-gateway-for-woocommerce'), sanitize_text_field($requestBody['order_status']), $requestBody['payment_id']);
                    $order->update_status($newOrderStatus, $orderNote);
                    break;
                default:
                    throw new Exception (__('Unhandled flitt order status', 'flitt-payment-gateway-for-woocommerce'));
            }
        } catch (Exception $e) {
            if (!empty($order))
                $order->update_status('failed', $e->getMessage());
            wp_send_json(['error' => $e->getMessage()], 400);
        }

        status_header(200);
        exit;
    }

    /**
     * Flitt payment complete process
     *
     * @param WC_Order $order
     * @param $transactionID
     * @since 3.0.0
     */
    public function flittPaymentComplete($order, $transactionID)
    {
        if (!$order->is_paid()) {
            $order->payment_complete($transactionID);
            /* translators: flitt order id */
            $orderNote = sprintf(__('Flitt payment successful.<br/>Flitt ID: %1$s<br/>', 'flitt-payment-gateway-for-woocommerce'), $transactionID);

            if ($this->completed_order_status != 'default') {
                WC()->cart->empty_cart();
                $order->update_status($this->completed_order_status, $orderNote);
            } else $order->add_order_note($orderNote);
        }
    }
}
