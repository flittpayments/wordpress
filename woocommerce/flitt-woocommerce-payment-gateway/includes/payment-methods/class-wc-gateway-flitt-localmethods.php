<?php


class WC_Gateway_Flitt_LocalMethods extends WC_Flitt_Payment_Gateway
{
    use Flitt_Embedded;

    public function __construct()
    {
        $this->id = 'flitt_local_methods'; // payment gateway plugin ID
        $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = false; // in case you need a custom credit card form
        $this->method_title = 'Flitt Local Methods';
        $this->method_description = sprintf(
            __('All other general Flitt settings can be adjusted <a href="%s">here</a>.', 'flitt-woocommerce-payment-gateway'),
            admin_url('admin.php?page=wc-settings&tab=checkout&section=flitt')
        );

        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $main_settings = get_option('woocommerce_flitt_settings');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->integration_type = $this->get_option('integration_type') ? $this->get_option('integration_type') : false;
        $this->merchant_id = !empty($main_settings['merchant_id']) ? $main_settings['merchant_id'] : '';
        $this->secret_key = !empty($main_settings['secret_key']) ? $main_settings['secret_key'] : '';
        $this->test_mode = !empty($main_settings['test_mode']) && 'yes' === $main_settings['test_mode'];
        $this->redirect_page_id = !empty($main_settings['redirect_page_id']) ? $main_settings['redirect_page_id'] : false;
        $this->completed_order_status = !empty($main_settings['completed_order_status']) ? $main_settings['completed_order_status'] : false;
        $this->expired_order_status = !empty($main_settings['expired_order_status']) ? $main_settings['expired_order_status'] : false;
        $this->declined_order_status = !empty($main_settings['declined_order_status']) ? $main_settings['declined_order_status'] : false;

        parent::__construct();
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => 'Enable/Disable',
                'label' => 'Enable Flitt Local Methods Gateway',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ],
            'title' => [
                'title' => __('Title', 'flitt-woocommerce-payment-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout', 'flitt-woocommerce-payment-gateway'),
                'default' => __('Flitt Local Methods', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => 'Description',
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout', 'flitt-woocommerce-payment-gateway'),
                'default' => __('Pay with your local bank with Flitt online payments.', 'flitt-woocommerce-payment-gateway'),
            ],
            'integration_type' => [
                'title' => __('Payment integration type', 'flitt-woocommerce-payment-gateway'),
                'type' => 'select',
                'options' => $this->getIntegrationTypes(),
                'description' => __('How the payment form will be displayed', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
        ];
    }

    public function getPaymentOptions()
    {
        $paymentOptions = parent::getPaymentOptions();

        $paymentOptions['methods'] = ['local_methods'];
        $paymentOptions['methods_disabled'] = ['wallets', 'card', 'banklinks_eu'];
        $paymentOptions['active_tab'] = 'local_methods';

        return $paymentOptions;
    }
}
