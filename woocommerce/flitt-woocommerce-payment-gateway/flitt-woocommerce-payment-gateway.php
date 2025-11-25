<?php
/**
 * Plugin Name: WooCommerce - Flitt payment gateway
 * Plugin URI: https://flitt.com
 * Description: Flitt Payment Gateway for WooCommerce.
 * Author: Flitt
 * Author URI: https://flitt.com
 * Version: 4.0.0
 * Text Domain: flitt-woocommerce-payment-gateway
 * Domain Path: /languages
 * Tested up to: 5.8
 * WC tested up to: 5.6
 * WC requires at least: 3.0
 *
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;

define("WC_FLITT_DIR", dirname(__FILE__));
define("WC_FLITT_BASE_FILE", __FILE__);
define('WC_FLITT_VERSION', '4.0.0');
define('WC_FLITT_MIN_PHP_VER', '5.6.0');
define('WC_FLITT_MIN_WC_VER', '3.0');

add_action('plugins_loaded', 'woocommerce_gateway_flitt');

if ( ! class_exists( 'WC_Flitt' ) ) {
    class WC_Flitt
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

            require_once dirname(__FILE__) . '/includes/abstract-wc-flitt-payment-gateway.php';
            require_once dirname(__FILE__) . '/includes/payment-methods/class-wc-gateway-flitt-card.php';

            require_once dirname(__FILE__) . '/includes/compat/class-wc-flitt-pre-orders-compat.php';
            require_once dirname(__FILE__) . '/includes/compat/class-wc-flitt-subscriptions-compat.php';

            // This action hook registers our PHP class as a WooCommerce payment gateway
            add_filter('woocommerce_payment_gateways', [$this, 'add_gateways']);
            // localization
            load_plugin_textdomain('flitt-woocommerce-payment-gateway', false, basename(WC_FLITT_DIR) . '/languages/');
            // add plugin setting button
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links']);

            $this->updateSettings();
            add_action('before_woocommerce_init',  [$this, 'declare_cart_checkout_blocks_compatibility']);
            add_action('before_woocommerce_init',  [$this, 'declare_flitt_hpos_compatibility']);
            add_action( 'woocommerce_blocks_loaded',  [$this, 'flitt_register_order_approval_payment_method_type']);

        }

        public function add_gateways($gateways)
        {
            $gateways[] = 'WC_Gateway_Flitt_Card';
            // $gateways[] = 'WC_Gateway_Flitt_Bank';
            // $gateways[] = 'WC_Gateway_Flitt_LocalMethods';
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
                    __('Settings', 'flitt-woocommerce-payment-gateway')
                ),
            ];

            return array_merge($plugin_links, $links);
        }

        /**
         * migrate old settings
         */
        public function updateSettings()
        {
            if (version_compare(get_option('flitt_woocommerce_version'), WC_FLITT_VERSION, '<')) {
                update_option('flitt_woocommerce_version', WC_FLITT_VERSION);
                $settings = maybe_unserialize(get_option('woocommerce_flitt_settings', []));

                if (isset($settings['salt'])) {
                    $settings['flitt_secret_key'] = $settings['salt'];
                    unset($settings['salt']);
                }

                if (isset($settings['default_order_status'])){
                    $settings['completed_order_status'] = $settings['default_order_status'];
                    unset($settings['default_order_status']);
                }

                if (isset($settings['payment_type'])) {
                    switch ($settings['payment_type']) {
                        case 'page_mode':
                            $settings['integration_type'] = 'embedded';
                            break;
                        case 'on_checkout_page':
                            $settings['integration_type'] = 'seamless';
                            break;
                        default:
                            $settings['integration_type'] = 'hosted';
                    }
                    unset($settings['payment_type']);
                }

                unset($settings['calendar']);
                unset($settings['page_mode_instant']);
                unset($settings['on_checkout_page']);
                unset($settings['force_lang']);

                update_option('woocommerce_flitt_settings', $settings);
            }
        }

        /**
         * check env
         *
         * @return bool
         */
        public function isAcceptableEnv()
        {
            if (version_compare(WC_VERSION, WC_FLITT_MIN_WC_VER, '<')) {
                add_action('admin_notices', [$this, 'woocommerce_flitt_wc_not_supported_notice']);
                return false;
            }

            if (version_compare(phpversion(), WC_FLITT_MIN_PHP_VER, '<')) {
                add_action('admin_notices', [$this, 'woocommerce_flitt_php_not_supported_notice']);

                return false;
            }

            return true;
        }

        public function woocommerce_flitt_wc_not_supported_notice()
        {
            /* translators: 1) required WC version 2) current WC version */
            $message = sprintf(__('Payment Gateway Flitt requires WooCommerce %1$s or greater to be installed and active. WooCommerce %2$s is no longer supported.', 'flitt-woocommerce-payment-gateway'), WC_FLITT_MIN_WC_VER, WC_VERSION);
            echo '<div class="notice notice-error is-dismissible"> <p>' . $message . '</p></div>';
        }

        public function woocommerce_flitt_php_not_supported_notice()
        {
            /* translators: 1) required PHP version 2) current PHP version */
            $message = sprintf(__('The minimum PHP version required for Flitt Payment Gateway is %1$s. You are running %2$s.', 'flitt-woocommerce-payment-gateway'), WC_FLITT_MIN_PHP_VER, phpversion());
            echo '<div class="notice notice-error is-dismissible"> <p>' . $message . '</p></div>';
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

function woocommerce_gateway_flitt() {
    return WC_Flitt::getInstance();
}
