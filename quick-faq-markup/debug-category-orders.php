<?php
/**
 * Debug script to check category-specific orders in database
 */

// Make sure we're in WordPress context
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

global $wpdb;

echo "<h2>Debug: Category-Specific Orders</h2>";

// Get all FAQ posts
$faqs = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.menu_order
    FROM {$wpdb->posts} p
    WHERE p.post_type = 'qfm_faq' 
    AND p.post_status = 'publish'
    ORDER BY p.menu_order ASC
");

echo "<h3>FAQ Posts:</h3>";
foreach ($faqs as $faq) {
    echo "<div style='margin-bottom: 20px; padding: 10px; border: 1px solid #ddd;'>";
    echo "<strong>FAQ ID: {$faq->ID}</strong> - {$faq->post_title} (menu_order: {$faq->menu_order})<br>";
    
    // Get all meta fields for this FAQ
    $meta_fields = $wpdb->get_results($wpdb->prepare("
        SELECT meta_key, meta_value
        FROM {$wpdb->postmeta}
        WHERE post_id = %d
        AND (meta_key LIKE '_qfm_faq_order_%' OR meta_key = '_qfm_faq_order_uncategorized')
        ORDER BY meta_key
    ", $faq->ID));
    
    if ($meta_fields) {
        echo "<strong>Category-specific orders:</strong><br>";
        foreach ($meta_fields as $meta) {
            echo "  - {$meta->meta_key}: {$meta->meta_value}<br>";
        }
    } else {
        echo "<em>No category-specific order meta fields found</em><br>";
    }
    
    // Get categories for this FAQ
    $categories = $wpdb->get_results($wpdb->prepare("
        SELECT t.term_id, t.name, t.slug
        FROM {$wpdb->terms} t
        INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
        WHERE tr.object_id = %d
        AND tt.taxonomy = 'qfm_faq_category'
    ", $faq->ID));
    
    if ($categories) {
        echo "<strong>Categories:</strong><br>";
        foreach ($categories as $cat) {
            echo "  - {$cat->name} (ID: {$cat->term_id}, slug: {$cat->slug})<br>";
        }
    } else {
        echo "<em>No categories assigned</em><br>";
    }
    
    echo "</div>";
}

// Get General category info
$general_category = $wpdb->get_row("
    SELECT term_id, name, slug
    FROM {$wpdb->terms} t
    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
    WHERE tt.taxonomy = 'qfm_faq_category'
    AND t.slug = 'general'
");

echo "<h3>General Category Info:</h3>";
if ($general_category) {
    echo "ID: {$general_category->term_id}, Name: {$general_category->name}, Slug: {$general_category->slug}<br>";
    
    // Check for expected meta keys
    echo "<h4>Expected meta keys for General category:</h4>";
    echo "Should be: _qfm_faq_order_{$general_category->term_id}<br>";
    
    // Check actual meta keys
    $actual_meta_keys = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT meta_key, COUNT(*) as count
        FROM {$wpdb->postmeta}
        WHERE meta_key = %s
        GROUP BY meta_key
    ", '_qfm_faq_order_' . $general_category->term_id));
    
    if ($actual_meta_keys) {
        echo "<strong>Actual meta keys found:</strong><br>";
        foreach ($actual_meta_keys as $meta) {
            echo "  - {$meta->meta_key}: {$meta->count} records<br>";
        }
    } else {
        echo "<em>No matching meta keys found!</em><br>";
    }
} else {
    echo "<em>General category not found!</em><br>";
}

echo "<h3>All Category-Specific Meta Keys:</h3>";
$all_category_meta = $wpdb->get_results("
    SELECT DISTINCT meta_key, COUNT(*) as count
    FROM {$wpdb->postmeta}
    WHERE meta_key LIKE '_qfm_faq_order_%'
    GROUP BY meta_key
    ORDER BY meta_key
");

if ($all_category_meta) {
    foreach ($all_category_meta as $meta) {
        echo "  - {$meta->meta_key}: {$meta->count} records<br>";
    }
} else {
    echo "<em>No category-specific meta keys found!</em><br>";
}