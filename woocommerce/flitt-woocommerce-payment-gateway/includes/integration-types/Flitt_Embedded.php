<?php

trait Flitt_Embedded
{
    public $embedded = true;

    public function includeEmbeddedAssets()
    {
        // we need JS only on cart/checkout pages
        // if our payment gateway is disabled, we do not have to enqueue JS too
        if ('no' === $this->enabled || (!is_cart() && !is_checkout_pay_page())) {
            return;
        }

        wp_enqueue_style('flitt-vue-css', 'https://pay.flitt.com/latest/checkout-vue/checkout.css', null, WC_FLITT_VERSION);
        wp_enqueue_script('flitt-vue-js', 'https://pay.flitt.com/latest/checkout-vue/checkout.js', null, WC_FLITT_VERSION);

        //wp_register_script('flitt-init', plugins_url('assets/js/flitt_embedded.js', WC_FLITT_BASE_FILE), ['flitt-vue-js'], WC_FLITT_VERSION);

        wp_enqueue_style('flitt-embedded', plugins_url('assets/css/flitt_embedded.css', WC_FLITT_BASE_FILE), ['storefront-woocommerce-style', 'flitt-vue-css'], WC_FLITT_VERSION);
    }

    public function receipt_page($order_id)
    {
        $order = wc_get_order($order_id);

        try {
            $paymentArguments = [
                'options' => $this->getPaymentOptions(),
                'params' => ['token' => $this->getCheckoutToken($order)],
            ];
        } catch (Exception $e) {
            wp_die($e->getMessage());
        }
        wp_enqueue_script('flitt-init',
            plugins_url('assets/js/flitt_embedded.js', WC_FLITT_BASE_FILE), ['flitt-vue-js'], WC_FLITT_VERSION);
        wp_localize_script('flitt-init', 'FlittPaymentArguments', $paymentArguments);

        echo '<div id="flitt-checkout-container"></div>';
    }
}