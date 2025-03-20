<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Flitt_Gateway_Blocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'flitt';// your payment gateway name

    public function initialize()
    {
        $this->gateway = new WC_Flitt_Payment_Gateway();
        $this->settings = get_option( 'woocommerce_flitt_settings', [] );
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'flitt-gateway-blocks-integration',
            plugins_url('assets/js/flitt_block.js', WC_FLITT_BASE_FILE),
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('flitt-gateway-blocks-integration');
        }
        return ['flitt-gateway-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->settings['title'],
            'description' => $this->settings['description'],
            'icon'        => plugins_url('/assets/img/logo.svg', __FILE__)
        ];
    }
}
