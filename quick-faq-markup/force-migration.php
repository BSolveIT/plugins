<?php
/**
 * Force migration script for debugging
 */

// Include WordPress
require_once 'C:\MAMP\htdocs\365i\wp-config.php';

// Get the category order instance
global $quick_faq_markup;
$category_order = $quick_faq_markup->get_category_order();

echo "=== FORCE MIGRATION DEBUG ===\n";

// Check current migration status
$status = get_option('qfm_category_order_migration_status', 'pending');
echo "Current migration status: " . $status . "\n";

// Force reset migration status
delete_option('qfm_category_order_migration_status');
delete_option('qfm_category_order_migration_status_time');
delete_option('qfm_category_order_migration_status_stats');
delete_option('qfm_category_order_migration_status_error');
delete_option('qfm_category_order_migration_status_notice_dismissed');

echo "Migration status reset.\n";

// Clear existing category orders
$category_order->reset_migration();

echo "Existing category orders cleared.\n";

// Force run migration
echo "Starting forced migration...\n";
$result = $category_order->run_migration();

if ($result) {
    echo "Migration completed successfully!\n";
    
    // Check the results
    $stats = get_option('qfm_category_order_migration_status_stats', array());
    echo "Migration stats: " . print_r($stats, true) . "\n";
} else {
    echo "Migration failed!\n";
    $error = get_option('qfm_category_order_migration_status_error', 'Unknown error');
    echo "Error: " . $error . "\n";
}

// Check the meta values after migration
global $wpdb;
$meta_values = $wpdb->get_results("
    SELECT post_id, meta_key, meta_value 
    FROM {$wpdb->postmeta} 
    WHERE meta_key LIKE '_qfm_faq_order_%' 
    ORDER BY post_id, meta_key
");

echo "\n=== META VALUES AFTER MIGRATION ===\n";
foreach ($meta_values as $meta) {
    echo "Post {$meta->post_id}: {$meta->meta_key} = {$meta->meta_value}\n";
}

echo "\n=== DONE ===\n";