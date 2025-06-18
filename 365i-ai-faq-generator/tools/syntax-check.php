<?php
/**
 * Syntax Check Script for 365i AI FAQ Generator
 *
 * This script performs a basic syntax check on the PHP files to ensure they're syntactically valid.
 * It uses PHP's lint feature to check syntax without loading classes into memory.
 *
 * Usage: php syntax-check.php [directory]
 * Example: php syntax-check.php ../includes/workers
 *
 * @package AI_FAQ_Generator
 */

echo "======================================\n";
echo "Running Syntax Check on PHP Files\n";
echo "======================================\n\n";

// Define the plugin directory
$plugin_dir = dirname(dirname(__FILE__));

// Allow checking a specific directory if passed as an argument
$check_dir = isset($argv[1]) ? $argv[1] : null;

if ($check_dir && is_dir($check_dir)) {
    echo "Checking directory: " . $check_dir . "\n\n";
    $base_dir = $check_dir;
} else {
    echo "Checking recent changes in workers system\n\n";
    $base_dir = $plugin_dir;
}

// Files to check
$files_to_check = [
    // Core files
    $plugin_dir . '/includes/class-ai-faq-workers.php',
    
    // Workers components
    $plugin_dir . '/includes/workers/class-ai-faq-workers-manager.php',
    $plugin_dir . '/includes/workers/components/class-ai-faq-workers-rate-limiter.php',
    $plugin_dir . '/includes/workers/components/class-ai-faq-workers-security.php',
    $plugin_dir . '/includes/workers/components/class-ai-faq-workers-analytics.php',
    $plugin_dir . '/includes/workers/components/class-ai-faq-workers-request-handler.php',
];

// If a specific directory was provided, scan for PHP files
if (isset($base_dir) && $base_dir !== $plugin_dir) {
    $files_to_check = [];
    $directory = new RecursiveDirectoryIterator($base_dir);
    $iterator = new RecursiveIteratorIterator($directory);
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files_to_check[] = $file->getPathname();
        }
    }
}

$total_files = count($files_to_check);
$valid_files = 0;
$invalid_files = 0;

foreach ($files_to_check as $file) {
    echo "Checking: " . basename($file) . "... ";
    
    // Use PHP's lint feature to check syntax WITHOUT loading classes
    $output = [];
    $return_var = 0;
    exec('php -l ' . escapeshellarg($file), $output, $return_var);
    
    if ($return_var === 0) {
        echo "✅ Valid PHP syntax\n";
        $valid_files++;
    } else {
        echo "❌ INVALID: " . implode("\n", $output) . "\n";
        $invalid_files++;
    }
}

echo "\n======================================\n";
echo "Syntax Check Results:\n";
echo "Total files checked: $total_files\n";
echo "Valid files: $valid_files\n";
echo "Invalid files: $invalid_files\n";
echo "======================================\n";

// Return non-zero exit code if any files had syntax errors
exit($invalid_files > 0 ? 1 : 0);