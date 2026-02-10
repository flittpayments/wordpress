<?php
/**
 * Plugin Name: Flitt payment gateway for WooCommerce
 * Plugin URI: https://flitt.com
 * Description: Flitt Payment Gateway for WooCommerce.
 * Author: Flitt
 * Author URI: https://github.com/flittpayments/wordpress/tree/main/woocommerce
 * Version: 4.0.3
 * Text Domain: flitt-payment-gateway-for-woocommerce
 * Domain Path: /languages
 * Tested up to: 6.9
 * WC tested up to: 5.6
 * WC requires at least: 3.0
 * Requires Plugins: woocommerce
 *
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// Make sure WooCommerce is active.
if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && ! class_exists( 'WooCommerce' ) ) {
    return;
}

define("FLITT_WC_PLUGIN_DIR", dirname(__FILE__));
define("FLITT_WC_BASE_FILE", __FILE__);
define('FLITT_WC_VERSION', '4.0.3');
define('FLITT_WC_MIN_PHP_VER', '5.6.0');
define('FLITT_WC_MIN_WC_VER', '3.0');

add_action('plugins_loaded', 'flitt_woocommerce_payment_gateway');

if ( ! class_exists( 'Flitt_PaymentGateway' ) ) {
    class Flitt_PaymentGateway
    {
        private static $instance = null;

        /**
         * gets the instance via lazy initialization (created on first usage)
         */
        public static function getInstance()
        {
            if (static::$instance === null) {
                static::$instance = new static();
            }

            return static::$instance;
        }
        function declare_cart_checkout_blocks_compatibility() {
            if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
            }
        }

        function declare_flitt_hpos_compatibility() {
            if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                    'custom_order_tables',
                    __FILE__,
                    true
                );
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                    'orders_cache',
                    __FILE__,
                    true
                    );
            }
        }

        function flitt_register_order_approval_payment_method_type() {
            if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
                return;
            }
            require_once plugin_dir_path(__FILE__) . 'class-block.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register( new Flitt_Gateway_Blocks );
                }
            );
        }

        private function __construct()
        {
            if (!$this->isAcceptableEnv())
                return;

            require_once dirname(__FILE__) . '/includes/integration-types/Flitt_Embedded.php';
            require_once dirname(__FILE__) . '/includes/integration-types/Flitt_Hosted.php';
            require_once dirname(__FILE__) . '/includes/integration-types/Flitt_Seamless.php';

            require_once dirname(__FILE__) . '/includes/abstract-flitt-payment-gateway.php';
            require_once dirname(__FILE__) . '/includes/payment-methods/class-gateway-flitt-card.php';

            require_once dirname(__FILE__) . '/includes/compat/class-flitt-pre-orders-compat.php';
            require_once dirname(__FILE__) . '/includes/compat/class-flitt-subscriptions-compat.php';

            // This action hook registers our PHP class as a WooCommerce payment gateway
            add_filter('woocommerce_payment_gateways', [$this, 'add_gateways']);
            // localization
            load_plugin_textdomain('flitt-payment-gateway-for-woocommerce', false, basename(FLITT_WC_PLUGIN_DIR) . '/languages/');
            // add plugin setting button
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links']);

            add_action('before_woocommerce_init',  [$this, 'declare_cart_checkout_blocks_compatibility']);
            add_action('before_woocommerce_init',  [$this, 'declare_flitt_hpos_compatibility']);
            add_action( 'woocommerce_blocks_loaded',  [$this, 'flitt_register_order_approval_payment_method_type']);

        }

        public function add_gateways($gateways)
        {
            $gateways[] = 'Flitt_WC_Gateway_Card';
            return $gateways;
        }

        /**
         * render setting button in wp plugins list
         *
         * @param $links
         * @return array|string[]
         */
        public function plugin_action_links($links)
        {
            $plugin_links = [
                sprintf(
                    '<a href="%1$s">%2$s</a>',
                    admin_url('admin.php?page=wc-settings&tab=checkout&section=flitt'),
                    __('Settings', 'flitt-payment-gateway-for-woocommerce')
                ),
            ];

            return array_merge($plugin_links, $links);
        }

        /**
         * check env
         *
         * @return bool
         */
        public function isAcceptableEnv()
        {
            if (version_compare(WC_VERSION, FLITT_WC_MIN_WC_VER, '<')) {
                add_action('admin_notices', [$this, 'woocommerce_flitt_wc_not_supported_notice']);
                return false;
            }

            if (version_compare(phpversion(), FLITT_WC_MIN_PHP_VER, '<')) {
                add_action('admin_notices', [$this, 'woocommerce_flitt_php_not_supported_notice']);

                return false;
            }

            return true;
        }

        public function woocommerce_flitt_wc_not_supported_notice()
        {
            /* translators: 1) required WC version 2) current WC version */
            $message = sprintf(esc_html__('Payment Gateway Flitt requires WooCommerce %1$s or greater to be installed and active. WooCommerce %2$s is no longer supported.', 'flitt-payment-gateway-for-woocommerce'), FLITT_WC_MIN_WC_VER, WC_VERSION);
            echo '<div class="notice notice-error is-dismissible"> <p>' . esc_html( $message ) . '</p></div>';
        }

        public function woocommerce_flitt_php_not_supported_notice()
        {
            /* translators: 1) required PHP version 2) current PHP version */
            $message = sprintf(esc_html__('The minimum PHP version required for Flitt Payment Gateway is %1$s. You are running %2$s.', 'flitt-payment-gateway-for-woocommerce'), FLITT_WC_MIN_PHP_VER, phpversion());
            echo '<div class="notice notice-error is-dismissible"> <p>' . esc_html( $message ) . '</p></div>';
        }

        /**
         * prevent from being unserialized (which would create a second instance of it)
         */
        public function __wakeup()
        {
            throw new Exception("Cannot unserialize singleton");
        }
    }
}

function flitt_woocommerce_payment_gateway() {
    return Flitt_PaymentGateway::getInstance();
}
