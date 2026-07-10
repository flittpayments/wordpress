<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pay By Bank (open banking) payment method.
 *
 * Sends `payment_systems = opb`. The default "All available banks" selection sends
 * no `payment_method`, so the customer chooses their bank on the Flitt page and any
 * bank Flitt adds is covered without changing the plugin. A specific bank can instead
 * be selected to route straight to it. The known banks (TBC, BoG, Credo, Liberty) are
 * shown as logos at checkout.
 */
class Flitt_WC_Gateway_Opb extends Flitt_WC_Gateway_Alt
{
    protected $payment_systems = 'opb';
    protected $payment_method = '';
    protected $payment_method_required = false;
    // Open banking is not restricted in test mode: the customer can pick the Demo
    // Bank on the Flitt checkout page.
    protected $force_demo_bank_in_test = false;
    // Enables the "All available banks" option (sends no payment_method).
    protected $all_methods_value = 'all';

    public function __construct()
    {
        $this->id = 'flitt_opb';
        $this->method_title = __('Flitt - Pay By Bank', 'flitt-payment-gateway-for-woocommerce');
        $this->method_description = __('Open banking payment (Pay By Bank) via Flitt.', 'flitt-payment-gateway-for-woocommerce');
        $this->available_payment_methods = [
            'all' => ['label' => __('All available banks', 'flitt-payment-gateway-for-woocommerce'), 'logo' => ''],
            'tbc' => ['label' => __('TBC Bank', 'flitt-payment-gateway-for-woocommerce'), 'logo' => 'bank-tbc.svg'],
            'bog' => ['label' => __('Bank of Georgia', 'flitt-payment-gateway-for-woocommerce'), 'logo' => 'bank-bog.svg'],
            'credo' => ['label' => __('Credo Bank', 'flitt-payment-gateway-for-woocommerce'), 'logo' => 'bank-credo.svg'],
            'liberty' => ['label' => __('Liberty Bank', 'flitt-payment-gateway-for-woocommerce'), 'logo' => 'bank-liberty.svg'],
            'x' => ['label' => __('Demo Bank (test mode)', 'flitt-payment-gateway-for-woocommerce'), 'logo' => ''],
        ];
        // Default to "All available banks": sends no payment_method so the customer
        // chooses their bank on the Flitt page (works in test mode too, where the
        // Demo Bank is offered there), while still showing the known bank logos.
        $this->default_payment_methods = ['all'];
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
