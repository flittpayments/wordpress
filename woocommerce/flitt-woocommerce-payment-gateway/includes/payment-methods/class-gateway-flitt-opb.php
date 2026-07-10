<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pay By Bank (open banking) payment method.
 *
 * Sends `payment_systems = opb`. With the default multi-bank selection no
 * `payment_method` is sent, so the customer chooses their bank on the Flitt page;
 * selecting a single bank routes straight to it. The enabled banks (TBC, BoG,
 * Credo, Liberty) are shown as logos at checkout.
 */
class Flitt_WC_Gateway_Opb extends Flitt_WC_Gateway_Alt
{
    protected $payment_systems = 'opb';
    protected $payment_method = '';
    protected $payment_method_required = false;
    // Open banking is not restricted in test mode: the customer can pick the Demo
    // Bank on the Flitt checkout page.
    protected $force_demo_bank_in_test = false;

    public function __construct()
    {
        $this->id = 'flitt_opb';
        $this->method_title = __('Flitt - Pay By Bank', 'flitt-payment-gateway-for-woocommerce');
        $this->method_description = __('Open banking payment (Pay By Bank) via Flitt.', 'flitt-payment-gateway-for-woocommerce');
        $this->available_payment_methods = [
            'tbc' => ['label' => __('TBC Bank', 'flitt-payment-gateway-for-woocommerce'), 'logo' => 'bank-tbc.svg'],
            'bog' => ['label' => __('Bank of Georgia', 'flitt-payment-gateway-for-woocommerce'), 'logo' => 'bank-bog.svg'],
            'credo' => ['label' => __('Credo Bank', 'flitt-payment-gateway-for-woocommerce'), 'logo' => 'bank-credo.svg'],
            'liberty' => ['label' => __('Liberty Bank', 'flitt-payment-gateway-for-woocommerce'), 'logo' => 'bank-liberty.svg'],
            'x' => ['label' => __('Demo Bank (test mode)', 'flitt-payment-gateway-for-woocommerce'), 'logo' => ''],
        ];
        // Default to all four banks: shows the four bank logos and sends no
        // payment_method, so the customer chooses their bank on the Flitt page
        // (works in test mode too, where the Demo Bank is offered there).
        $this->default_payment_methods = ['tbc', 'bog', 'credo', 'liberty'];
        // Admin uses the Flitt brand logo; bank logos are checkout-only.
        $this->icon = plugins_url('assets/img/logo.svg', FLITT_WC_BASE_FILE);

        $this->supports = ['products', 'refunds'];

        parent::__construct();
    }

    public function init_form_fields()
    {
        $fields = $this->get_shared_form_fields();
        $fields['title']['default'] = __('Pay By Bank', 'flitt-payment-gateway-for-woocommerce');
        $fields['description']['default'] = __('Pay directly from your bank account via open banking.', 'flitt-payment-gateway-for-woocommerce');
        $this->form_fields = $fields;
    }
}
