<?php

trait Flitt_Seamless
{
    public $seamless = true;

    public function includeSeamlessAssets()
    {
        // we need JS only on cart/checkout pages
        // if our payment gateway is disabled, we do not have to enqueue JS too
        if ('no' === $this->enabled || (!is_cart() && !is_checkout())) {
            return;
        }

        wp_enqueue_style('flitt-checkout', plugins_url('assets/css/flitt_seamless_old.css', FLITT_WC_BASE_FILE), array(), FLITT_WC_VERSION);
        wp_enqueue_script('flitt_pay_v2', plugins_url('checkout.js', FLITT_WC_BASE_FILE), ['jquery'], FLITT_WC_VERSION, true);
        wp_enqueue_script('flitt_pay_v2_woocom', plugins_url('assets/js/flitt_seamless.js', FLITT_WC_BASE_FILE), ['flitt_pay_v2'], FLITT_WC_VERSION, true);
        wp_enqueue_script('flitt_pay_v2_card', plugins_url('assets/js/payform.min.js', FLITT_WC_BASE_FILE), ['flitt_pay_v2_woocom'], FLITT_WC_VERSION, true);

        wp_localize_script('flitt_pay_v2_woocom', 'flitt_info',
            [
                'url' => WC_AJAX::get_endpoint('checkout'),
                'nonce' => wp_create_nonce('flitt-submit-nonce')
            ]
        );
    }

    public function payment_fields()
    {
        if ($this->integration_type === 'seamless') {
            ?>
            <form autocomplete="on" class="flitt-ccard" id="checkout_flitt_form">
            <input type="hidden" name="payment_system" value="card">
            <div class="f-container">
                <div class="input-wrapper">
                    <div class="input-label w-1">
                        <?php esc_html_e('Card Number:', 'flitt-payment-gateway-for-woocommerce') ?>
                    </div>
                    <div class="input-field w-1">
                        <input required type="tel" name="card_number" class="input flitt-credit-cart"
                               id="flitt_ccard"
                               autocomplete="cc-number"
                               placeholder="<?php esc_html_e('XXXXXXXXXXXXXXXX', 'flitt-payment-gateway-for-woocommerce') ?>"/>
                        <div id="f_card_sep"></div>
                    </div>
                </div>
                <div class="input-wrapper">
                    <div class="input-label w-3-2">
                        <?php esc_html_e('Expiry Date:', 'flitt-payment-gateway-for-woocommerce') ?>
                    </div>
                    <div class="input-label w-4 w-rigth">
                        <?php esc_html_e('CVV2:', 'flitt-payment-gateway-for-woocommerce') ?>
                    </div>
                    <div class="input-field w-4">
                        <input required type="tel" name="expiry_month" id="flitt_expiry_month"
                               onkeydown="nextInput(this,event)" class="input"
                               maxlength="2" placeholder="MM"/>
                    </div>
                    <div class="input-field w-4">
                        <input required type="tel" name="expiry_year" id="flitt_expiry_year"
                               onkeydown="nextInput(this,event)" class="input"
                               maxlength="2" placeholder="YY"/>
                    </div>
                    <div class="input-field w-4 w-rigth">
                        <input autocomplete="off" required type="tel" name="cvv2" id="flitt_cvv2"
                               onkeydown="nextInput(this,event)"
                               class="input"
                               placeholder="<?php esc_html_e('XXX', 'flitt-payment-gateway-for-woocommerce') ?>"/>
                    </div>
                </div>
                <div style="display: none" class="input-wrapper stack-1">
                    <div class="input-field w-1">
                        <input id="submit_flitt_checkout_form" type="submit" class="button"
                               value="<?php esc_html_e('Pay', 'flitt-payment-gateway-for-woocommerce') ?>"/>
                    </div>
                </div>
                <div class="error-wrapper"></div>
            </div>
            </form>
            <?php
        } else parent::payment_fields();
    }

    /**
     * Custom button order
     * @param $button
     * @return string
     */
    public function custom_order_button_html($button)
    {
        $order_button_text = __('Place order', 'flitt-payment-gateway-for-woocommerce');
        $js_event = "flitt_submit_order(event);";
        $button = '<button type="submit" onClick="' . esc_attr($js_event) . '" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr($order_button_text) . '" data-value="' . esc_attr($order_button_text) . '" >' . esc_attr($order_button_text) . '</button>';

        return $button;
    }

    /**
     * Process checkout func
     */
    public function flitt_generate_ajax_order_info()
    {
        check_ajax_referer('flitt-submit-nonce', 'nonce_code');
        wc_maybe_define_constant('WOOCOMMERCE_CHECKOUT', true);
        WC()->checkout()->process_checkout();
        wp_die(0);
    }
}
