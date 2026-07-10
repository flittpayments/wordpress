<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base class for Flitt "alternative" payment methods (installments, open banking).
 *
 * These methods are displayed as separate buttons on the checkout page but share
 * the merchant_id / secret key and behaviour configured on the main Flitt gateway
 * (option `woocommerce_flitt_settings`). Their own settings expose enable/disable,
 * title, description and a bank/payment-method multiselect.
 *
 * The redirect (hosted) flow is used because installments and open banking are only
 * supported for the Redirect/Iframe or Embedded flows.
 */
abstract class Flitt_WC_Gateway_Alt extends Flitt_WC_Payment_Gateway
{
    use Flitt_Hosted;

    /**
     * Value sent in the `payment_systems` order parameter.
     *
     * @var string
     */
    protected $payment_systems = '';

    /**
     * Default value sent in the `payment_method` order parameter when it cannot be
     * derived from the admin selection. Empty means "not sent".
     *
     * @var string
     */
    protected $payment_method = '';

    /**
     * Whether Flitt requires the `payment_method` parameter for this method.
     * Installments require it; open banking does not.
     *
     * @var bool
     */
    protected $payment_method_required = false;

    /**
     * Whether test mode forces the Demo Bank (`x`). Installments only work with the
     * Demo Bank in test mode; open banking lets the customer pick the Demo Bank on
     * the Flitt checkout page, so it is not restricted.
     *
     * @var bool
     */
    protected $force_demo_bank_in_test = true;

    /**
     * Predefined payment methods the merchant can pick from in the admin
     * multiselect. Keyed by the Flitt `payment_method` value.
     *
     * value => [ 'label' => string, 'logo' => string (asset filename, may be empty) ]
     *
     * @var array
     */
    protected $available_payment_methods = [];

    /**
     * Default selection for the multiselect field.
     *
     * @var string[]
     */
    protected $default_payment_methods = [];

    public function __construct()
    {
        $this->has_fields = false;

        // Shared credentials and behaviour (incl. test mode) come from the main Flitt
        // gateway. Load them first so the form fields can react to test mode.
        $this->loadSharedSettings();

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->maybe_translate($this->get_option('title'));
        $this->description = $this->maybe_translate($this->get_option('description'));
        $this->enabled = $this->get_option('enabled');

        // Installments / open banking only support the redirect (hosted) flow.
        $this->integration_type = 'hosted';

        parent::__construct();
    }

    /**
     * Load merchant credentials and order-status behaviour from the main Flitt gateway.
     */
    protected function loadSharedSettings()
    {
        $main = get_option('woocommerce_flitt_settings', []);
        if (!is_array($main)) {
            $main = [];
        }

        // On a fresh install the main gateway option does not exist yet; test mode
        // defaults to ON (mirrors the main gateway's 'test_mode' field default).
        $test_mode_raw = isset($main['test_mode']) ? $main['test_mode'] : 'yes';
        $this->test_mode = 'yes' === $test_mode_raw;
        $this->debug_mode = isset($main['logging']) && 'yes' === $main['logging'];

        if ($this->test_mode) {
            $this->flitt_merchant_id = self::TEST_MERCHANT_ID;
            $this->flitt_secret_key = self::TEST_MERCHANT_SECRET_KEY;
        } else {
            $this->flitt_merchant_id = isset($main['flitt_merchant_id']) ? (int) $main['flitt_merchant_id'] : 0;
            $this->flitt_secret_key = isset($main['flitt_secret_key']) ? $main['flitt_secret_key'] : '';
        }

        $this->redirect_page_id = isset($main['redirect_page_id']) ? $main['redirect_page_id'] : '';
        $this->completed_order_status = isset($main['completed_order_status']) ? $main['completed_order_status'] : false;
        $this->expired_order_status = isset($main['expired_order_status']) ? $main['expired_order_status'] : false;
        $this->declined_order_status = isset($main['declined_order_status']) ? $main['declined_order_status'] : false;
    }

    /**
     * Keep credentials sourced from the main Flitt gateway on every re-init
     * (process_payment / callback / refund call set_params()).
     */
    public function set_params()
    {
        if (empty($this->form_fields)) {
            $this->init_form_fields();
        }
        if (!is_array($this->settings) || empty($this->settings)) {
            $this->init_settings();
        }
        $this->loadSharedSettings();
    }

    /**
     * Common enable/disable + title/description fields for every alternative method.
     *
     * @return array
     */
    protected function get_shared_form_fields()
    {
        $fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable this payment method', 'flitt-payment-gateway-for-woocommerce'),
                'default' => 'no',
                'description' => __('Show in the Payment List as a payment option', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true,
            ],
            'title' => [
                'title' => __('Title', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description:', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout', 'flitt-payment-gateway-for-woocommerce'),
                'desc_tip' => true,
            ],
        ];

        if (!empty($this->available_payment_methods)) {
            $options = [];
            foreach ($this->available_payment_methods as $value => $data) {
                $options[$value] = $data['label'];
            }

            if ($this->payment_method_required) {
                $desc = __('Choose which bank(s) to offer. Their logos are shown at checkout. If exactly one is selected the customer is routed straight to it; otherwise the default bank is used.', 'flitt-payment-gateway-for-woocommerce');
            } else {
                $desc = __('Choose which bank(s) to offer. Their logos are shown at checkout. If exactly one is selected the customer is routed straight to it; if several are selected the customer picks the bank on the Flitt page.', 'flitt-payment-gateway-for-woocommerce');
            }

            $desc_tip = true;

            // In test mode only the Demo Bank works, so warn the merchant inline (in red)
            // and ignore the bank selection at request time (see resolvePaymentMethod()).
            if ($this->test_mode) {
                $desc .= '<br /><span style="color:#b32d2e;font-weight:600;">'
                    . esc_html__('Test mode enabled. Only Demo bank will work.', 'flitt-payment-gateway-for-woocommerce')
                    . '</span>';
                $desc_tip = false;
            }

            $fields['payment_method'] = [
                'title' => __('Payment methods / banks', 'flitt-payment-gateway-for-woocommerce'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 400px;',
                'options' => $options,
                'default' => $this->default_payment_methods,
                'description' => $desc,
                'desc_tip' => $desc_tip,
            ];
        }

        return $fields;
    }

    /**
     * Sanitised list of payment methods selected in the admin, limited to the
     * predefined values.
     *
     * @return string[]
     */
    protected function get_selected_payment_methods()
    {
        $selected = $this->get_option('payment_method', $this->default_payment_methods);

        if (is_string($selected)) {
            $selected = array_filter(array_map('trim', explode(',', $selected)));
        }
        if (!is_array($selected)) {
            $selected = [];
        }

        return array_values(array_intersect($selected, array_keys($this->available_payment_methods)));
    }

    /**
     * Notice that credentials are shared with the main Flitt gateway.
     */
    public function admin_options()
    {
        $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=flitt');
        echo '<h2>' . esc_html($this->get_method_title()) . '</h2>';
        echo '<div class="notice notice-info inline"><p>' . wp_kses_post(
            sprintf(
                /* translators: %s: URL of the main Flitt gateway settings */
                __('This method uses the Merchant ID, Secret Key and test/debug settings from the main <a href="%s">Flitt</a> gateway.', 'flitt-payment-gateway-for-woocommerce'),
                esc_url($settings_url)
            )
        ) . '</p></div>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    /**
     * Append the payment_systems / payment_method parameters required by Flitt.
     *
     * @param WC_Order $order
     * @return array
     */
    public function getPaymentParams($order)
    {
        $params = parent::getPaymentParams($order);

        if (!empty($this->payment_systems)) {
            $params['payment_systems'] = $this->payment_systems;
        }

        $method = $this->resolvePaymentMethod();
        if (!empty($method)) {
            $params['payment_method'] = $method;
        }

        return $params;
    }

    /**
     * Resolve the single `payment_method` value to send from the admin selection.
     *
     * - Exactly one selected: route directly to it.
     * - None / several selected: for methods that require it, fall back to the
     *   default; otherwise omit it so the customer chooses on the Flitt page.
     *
     * @return string
     */
    protected function resolvePaymentMethod()
    {
        // In test mode installments only work with the Demo Bank. Open banking is not
        // restricted: the customer can still pick the Demo Bank on the Flitt page.
        if ($this->test_mode && $this->force_demo_bank_in_test) {
            return 'x';
        }

        $selected = $this->get_selected_payment_methods();

        if (count($selected) === 1) {
            return $selected[0];
        }

        if ($this->payment_method_required) {
            return $this->payment_method;
        }

        return '';
    }

    /**
     * Render one or more logos next to the method on the classic checkout.
     *
     * @return string
     */
    public function get_icon()
    {
        $html = '';
        foreach ($this->get_method_logos() as $logo) {
            $html .= sprintf(
                '<img src="%1$s" alt="%2$s" style="max-height:24px;width:auto;margin-left:4px;display:inline-block;vertical-align:middle;" />',
                esc_url($logo),
                esc_attr($this->get_title())
            );
        }

        return apply_filters('woocommerce_gateway_icon', $html, $this->id);
    }

    /**
     * Logo URLs for the currently selected methods, exposed to both the classic
     * and the block-based checkout. Falls back to all predefined logos when
     * nothing is selected.
     *
     * @return string[]
     */
    public function get_method_logos()
    {
        // In the admin panel always show the Flitt brand logo; the individual bank /
        // installment logos are used only on the frontend checkout.
        if ($this->is_admin_display()) {
            return [plugins_url('assets/img/logo.svg', FLITT_WC_BASE_FILE)];
        }

        $selected = $this->get_selected_payment_methods();
        if (empty($selected)) {
            $selected = array_keys($this->available_payment_methods);
        }

        $logos = [];
        foreach ($selected as $value) {
            if (!empty($this->available_payment_methods[$value]['logo'])) {
                $logos[] = plugins_url('assets/img/' . $this->available_payment_methods[$value]['logo'], FLITT_WC_BASE_FILE);
            }
        }

        return $logos;
    }
}
