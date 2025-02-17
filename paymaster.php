<?php
/*
 * Plugin Name: PayMaster for WooCommerce
 * Description: The payment module for accepting payments via the PayMaster service
 * Version: 1.0
 * Author: PayMaster
 * Text Domain:       paymaster
 * Domain Path:       /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

add_action('plugins_loaded', 'woocommerce_paymaster_plugin');

function woocommerce_paymaster_plugin()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
        
    load_plugin_textdomain( 'paymaster', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    
    require plugin_dir_path(__FILE__) . 'classes/WC_PayMaster_Gateway.php';
    
    add_filter('woocommerce_payment_gateways', 'add_paymaster_payment_gateway');
}

function add_paymaster_payment_gateway($methods)
{
    $methods[] = 'WC_PayMaster_Gateway';
    
    return $methods;
}

function wc_paymaster_plugin_activate()
{
    $settingsFileName = dirname(__FILE__) . '/settings.json';
    if (file_exists($settingsFileName)) {
        $fileContent = file_get_contents($settingsFileName);
        $settings = json_decode($fileContent);

        require plugin_dir_path(__FILE__) . 'classes/WC_PayMaster_Gateway.php';
        $pm = new WC_PayMaster_Gateway();
        $pm->update_option('base_service_address', $settings->base_service_url);
        $pm->update_option('title', $settings->service_name);
        $pm->update_option('description', $settings->service_description);
        $pm->update_option('send_receipt_data', $settings->send_receipt_data);
    }
}

register_activation_hook(__FILE__, 'wc_paymaster_plugin_activate');