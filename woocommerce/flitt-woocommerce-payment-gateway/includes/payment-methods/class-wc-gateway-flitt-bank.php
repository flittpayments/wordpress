<?php

class WC_Gateway_Flitt_Bank extends WC_Flitt_Payment_Gateway
{
    use Flitt_Hosted;
    use Flitt_Embedded;

    public function __construct()
    {
        $this->id = 'flitt_bank'; // payment gateway plugin ID
        $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = false; // in case you need a custom credit card form
        $this->method_title = 'Flitt Bank';
        $this->method_description = sprintf( //translators: link
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
                'title' => __('Enable/Disable', 'flitt-woocommerce-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Flitt Bank Gateway', 'flitt-woocommerce-payment-gateway'),
                'default' => 'no',
                'description' => __('Show in the Payment List as a payment option', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true
            ],
            'title' => [
                'title' => __('Title', 'flitt-woocommerce-payment-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout', 'flitt-woocommerce-payment-gateway'),
                'default' => __('Flitt Online Banking', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => 'Description',
                'type' => 'textarea',
                'default' => __('Payments with online banking.', 'flitt-woocommerce-payment-gateway'),
                'description' => __('This controls the description which the user sees during checkout', 'flitt-woocommerce-payment-gateway'),
                'desc_tip' => true,
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

        $paymentOptions['methods'] = ['banklinks_eu'];
        $paymentOptions['methods_disabled'] = ['wallets', 'card', 'local_methods'];
        $paymentOptions['active_tab'] = 'banklinks_eu';

        return $paymentOptions;
    }

    public function getPaymentParams($order)
    {
        $params = parent::getPaymentParams($order);

        if ($this->integration_type === 'hosted') {
            $params['payment_systems'] = 'banklinks_eu';
        }

        return $params;
    }
}
