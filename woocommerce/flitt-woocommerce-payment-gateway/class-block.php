<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Flitt_Gateway_Blocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'flitt';// your payment gateway name

    public function initialize()
    {
        $this->gateway = new Flitt_WC_Payment_Gateway();
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
            plugins_url('assets/js/flitt_block.js', FLITT_WC_BASE_FILE),
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            FLITT_WC_VERSION,
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('flitt-gateway-blocks-integration');
        }
        return ['flitt-gateway-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        // Admin (block editor) shows the Flitt brand logo only; the frontend checkout
        // shows Flitt + TBC + Apple Pay + Google Pay.
        $icons = $this->gateway->is_admin_display()
            ? [plugins_url('assets/img/logo.svg', FLITT_WC_BASE_FILE)]
            : Flitt_WC_Gateway_Card::checkout_logo_urls();

        $title = isset($this->settings['title']) ? $this->settings['title'] : '';
        $description = isset($this->settings['description']) ? $this->settings['description'] : '';

        return [
            'title' => $this->gateway->maybe_translate($title),
            'description' => $this->gateway->maybe_translate($description),
            'icon'        => plugins_url('/assets/img/logo.svg', __FILE__),
            'icons'       => $icons,
        ];
    }
}

/**
 * Shared block integration for the Flitt alternative payment methods
 * (installments, open banking).
 */
abstract class Flitt_Alt_Gateway_Blocks extends AbstractPaymentMethodType
{
    /**
     * @var Flitt_WC_Gateway_Alt
     */
    protected $gateway;

    /**
     * Handle used to register/enqueue the block script.
     *
     * @var string
     */
    protected $script_handle;

    /**
     * Block script filename inside assets/js/.
     *
     * @var string
     */
    protected $script_file;

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            $this->script_handle,
            plugins_url('assets/js/' . $this->script_file, FLITT_WC_BASE_FILE),
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            FLITT_WC_VERSION,
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations($this->script_handle);
        }
        return [$this->script_handle];
    }

    public function get_payment_method_data()
    {
        $title = isset($this->settings['title']) ? $this->settings['title'] : $this->gateway->get_method_title();
        $description = isset($this->settings['description']) ? $this->settings['description'] : '';

        return [
            'title' => $this->gateway->maybe_translate($title),
            'description' => $this->gateway->maybe_translate($description),
            'icons' => $this->gateway->get_method_logos(),
        ];
    }
}

final class Flitt_Installments_Gateway_Blocks extends Flitt_Alt_Gateway_Blocks
{
    protected $name = 'flitt_installments';
    protected $script_handle = 'flitt-installments-blocks-integration';
    protected $script_file = 'flitt_installments_block.js';

    public function initialize()
    {
        $this->gateway = new Flitt_WC_Gateway_Installments();
        $this->settings = get_option('woocommerce_flitt_installments_settings', []);
    }
}

final class Flitt_Opb_Gateway_Blocks extends Flitt_Alt_Gateway_Blocks
{
    protected $name = 'flitt_opb';
    protected $script_handle = 'flitt-opb-blocks-integration';
    protected $script_file = 'flitt_opb_block.js';

    public function initialize()
    {
        $this->gateway = new Flitt_WC_Gateway_Opb();
        $this->settings = get_option('woocommerce_flitt_opb_settings', []);
    }
}
