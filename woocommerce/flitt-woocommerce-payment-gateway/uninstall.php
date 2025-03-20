<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// if uninstall not called from WordPress exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options.
delete_option( 'flitt_woocommerce_version' );
delete_option( 'woocommerce_flitt_settings' );
delete_option( 'woocommerce_flitt_local_methods_settings' );
delete_option( 'woocommerce_flitt_bank_settings' );
delete_option( 'flitt_unique' ); // <3.0.0 option
