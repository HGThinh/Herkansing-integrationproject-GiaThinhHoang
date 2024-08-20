<?php
/*
Plugin Name: RabbitMQ Customer Sync
Description: A plugin to manage and synchronize customer data with RabbitMQ.
Version: 1.1
Author: Thinh
*/

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'rsc_activate_plugin');
register_deactivation_hook(__FILE__, 'rsc_deactivate_plugin');

// Include RabbitMQ dependencies
require_once __DIR__ . '/vendor/autoload.php'; // Ensure this path is correct

// Include other files
require_once __DIR__ . '/admin-pages.php';
require_once __DIR__ . '/ajax-handlers.php';
require_once __DIR__ . '/rabbitmq-test.php';
require_once __DIR__ . '/utils.php';

// Add admin menu
function rsc_add_admin_menu() {
    add_menu_page(
        'Klanten Overzicht',
        'Klanten Overzicht',
        'manage_options',
        'rsc_customer_sync',
        'rsc_customer_overview_page',
        'dashicons-admin-users'
    );

    add_submenu_page(
        'rsc_customer_sync',
        'Nieuwe Klant',
        'Nieuwe Klant',
        'manage_options',
        'rsc_add_customer',
        'rsc_add_customer_page'
    );
    
    add_submenu_page(
        'rsc_customer_sync',
        'Product Lijst',
        'Product Lijst',
        'manage_options',
        'rsc_product_list',
        'rsc_product_list_page'
    );
   

	
    add_submenu_page(
        'rsc_customer_sync',
        'Nieuwe Product',
        'Nieuwe Product',
        'manage_options',
        'rsc_add_product',
        'rsc_add_product_page' // Function to display the product management page
    );
   
    add_submenu_page(
        'rsc_customer_sync',
        'Test RabbitMQ',
        'Test RabbitMQ',
        'manage_options',
        'rsc_test_rabbitmq',
        'rabbitmq_test_admin_page_content'
    );
    
}
add_action('admin_menu', 'rsc_add_admin_menu');

// Activate plugin
function rsc_activate_plugin() {
    global $wpdb;
    $table_name = "{$wpdb->prefix}rsc_customers";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        email text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Deactivate plugin
function rsc_deactivate_plugin() {
    global $wpdb;
    $table_name = "{$wpdb->prefix}rsc_customers";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
?>
