<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * TBC Buy Now Pay Later (installments) payment method.
 *
 * Sends `payment_systems = installments`. The `payment_method` is the bank selected
 * in the admin (TBC by default), or the Demo Bank (`x`) in test mode.
 */
class Flitt_WC_Gateway_Installments extends Flitt_WC_Gateway_Alt
{
    protected $payment_systems = 'installments';
    protected $payment_method = 'tbc';
    protected $payment_method_required = true;

    public function __construct()
    {
        $this->id = 'flitt_installments';
        $this->method_title = __('Flitt - TBC Buy Now Pay Later', 'flitt-payment-gateway-for-woocommerce');
        $this->method_description = __('TBC Buy Now Pay Later (installments) via Flitt.', 'flitt-payment-gateway-for-woocommerce');
        $this->available_payment_methods = [
            'tbc' => ['label' => __('TBC Bank', 'flitt-payment-gateway-for-woocommerce'), 'logo' => 'installments-tbc.svg'],
            'x' => ['label' => __('Demo Bank (test mode)', 'flitt-payment-gateway-for-woocommerce'), 'logo' => ''],
        ];
        // Default to the Demo Bank so a fresh (test-mode) install works out of the box.
        $this->default_payment_methods = ['x'];
        // Admin uses the Flitt brand logo; bank/installment logos are checkout-only.
        $this->icon = plugins_url('assets/img/logo.svg', FLITT_WC_BASE_FILE);

        $this->supports = ['products', 'refunds'];

        parent::__construct();
    }

    public function init_form_fields()
    {
        $fields = $this->get_shared_form_fields();
        $fields['title']['default'] = __('TBC Buy Now Pay Later', 'flitt-payment-gateway-for-woocommerce');
        $fields['description']['default'] = __('Split your purchase into installments with TBC Bank.', 'flitt-payment-gateway-for-woocommerce');
        $this->form_fields = $fields;
    }

    /**
     * Always show the TBC logo on the checkout (this method is TBC-branded),
     * regardless of the admin bank selection. Admin keeps the Flitt logo.
     *
     * @return string[]
     */
    public function get_method_logos()
    {
        if ($this->is_admin_display()) {
            return [plugins_url('assets/img/logo.svg', FLITT_WC_BASE_FILE)];
        }

        return [
            plugins_url('assets/img/logo.svg', FLITT_WC_BASE_FILE),
            plugins_url('assets/img/installments-tbc.svg', FLITT_WC_BASE_FILE),
        ];
    }
}
