<?php
/**
 * Force migration script for debugging - web version
 */

// Only allow access from localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('Access denied');
}

// Include WordPress
define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

// Get the category order instance
global $quick_faq_markup;
$category_order = $quick_faq_markup->get_category_order();

echo "<h1>FORCE MIGRATION DEBUG</h1>";

// Check current migration status
$status = get_option('qfm_category_order_migration_status', 'pending');
echo "<p>Current migration status: " . $status . "</p>";

// Force reset migration status
delete_option('qfm_category_order_migration_status');
delete_option('qfm_category_order_migration_status_time');
delete_option('qfm_category_order_migration_status_stats');
delete_option('qfm_category_order_migration_status_error');
delete_option('qfm_category_order_migration_status_notice_dismissed');

echo "<p>Migration status reset.</p>";

// Clear existing category orders
$category_order->reset_migration();

echo "<p>Existing category orders cleared.</p>";

// Force run migration
echo "<p>Starting forced migration...</p>";
$result = $category_order->run_migration();

if ($result) {
    echo "<p><strong>Migration completed successfully!</strong></p>";
    
    // Check the results
    $stats = get_option('qfm_category_order_migration_status_stats', array());
    echo "<p>Migration stats: <pre>" . print_r($stats, true) . "</pre></p>";
} else {
    echo "<p><strong>Migration failed!</strong></p>";
    $error = get_option('qfm_category_order_migration_status_error', 'Unknown error');
    echo "<p>Error: " . $error . "</p>";
}

// Check the meta values after migration
global $wpdb;
$meta_values = $wpdb->get_results("
    SELECT post_id, meta_key, meta_value 
    FROM {$wpdb->postmeta} 
    WHERE meta_key LIKE '_qfm_faq_order_%' 
    ORDER BY post_id, meta_key
");

echo "<h2>META VALUES AFTER MIGRATION</h2>";
foreach ($meta_values as $meta) {
    echo "<p>Post {$meta->post_id}: {$meta->meta_key} = {$meta->meta_value}</p>";
}

echo "<h2>DONE</h2>";