<?php
/**
 * Component Test Script for 365i AI FAQ Generator
 *
 * This script tests the refactored components to ensure they're working correctly.
 * It loads the necessary files and instantiates the components to check for errors.
 *
 * Usage: php test-components.php
 *
 * @package AI_FAQ_Generator
 */

// Set up WordPress environment
define( 'WP_DEBUG', true );
$wp_load_path = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! file_exists( $wp_load_path ) ) {
    echo "WordPress environment not found. Exiting.\n";
    exit( 1 );
}

require_once $wp_load_path;

// Define plugin directory
if ( ! defined( 'AI_FAQ_GEN_DIR' ) ) {
    define( 'AI_FAQ_GEN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
}

/**
 * Run component tests
 */
function ai_faq_run_component_tests() {
    echo "======================================\n";
    echo "Running AI FAQ Generator Component Tests\n";
    echo "======================================\n\n";

    // Test 1: Test component loading
    echo "Test 1: Loading Dependencies...\n";
    try {
        require_once AI_FAQ_GEN_DIR . 'includes/workers/components/class-ai-faq-workers-security.php';
        require_once AI_FAQ_GEN_DIR . 'includes/workers/components/class-ai-faq-workers-rate-limiter.php';
        require_once AI_FAQ_GEN_DIR . 'includes/workers/components/class-ai-faq-workers-analytics.php';
        require_once AI_FAQ_GEN_DIR . 'includes/workers/components/class-ai-faq-workers-request-handler.php';
        require_once AI_FAQ_GEN_DIR . 'includes/workers/class-ai-faq-workers-manager.php';
        require_once AI_FAQ_GEN_DIR . 'includes/class-ai-faq-workers.php';
        
        echo "✅ Dependencies loaded successfully\n\n";
    } catch ( Exception $e ) {
        echo "❌ Failed to load dependencies: " . $e->getMessage() . "\n\n";
        return;
    }

    // Test 2: Test creating individual components
    echo "Test 2: Creating Individual Components...\n";
    
    try {
        $security = new AI_FAQ_Workers_Security();
        echo "✅ Security component created\n";
        
        // Get worker configuration from options
        $options = get_option( 'ai_faq_gen_options', array() );
        $workers_config = isset( $options['workers'] ) ? $options['workers'] : array();
        
        $rate_limiter = new AI_FAQ_Workers_Rate_Limiter( $workers_config );
        echo "✅ Rate Limiter component created\n";
        
        $analytics = new AI_FAQ_Workers_Analytics();
        echo "✅ Analytics component created\n";
        
        $manager = new AI_FAQ_Workers_Manager( $rate_limiter, $security, $analytics );
        echo "✅ Manager component created\n";
        
        echo "All components created successfully\n\n";
    } catch ( Exception $e ) {
        echo "❌ Failed to create components: " . $e->getMessage() . "\n\n";
        return;
    }

    // Test 3: Test creating the facade with proper dependencies
        echo "Test 3: Creating Facade with proper dependencies...\n";
        
        try {
            // Create workers facade with default configuration
            $workers = new AI_FAQ_Workers();
            echo "✅ Workers facade created with default configuration\n";
            
            // Create with empty config to test default worker config handling
            $empty_config_options = array( 'workers' => array() );
            update_option( 'ai_faq_gen_options_test', $empty_config_options );
            $workers_with_empty_config = new AI_FAQ_Workers( 'ai_faq_gen_options_test' );
            echo "✅ Workers facade created with empty configuration (tests default config fallback)\n\n";
        } catch ( Exception $e ) {
            echo "❌ Failed to create Workers facade: " . $e->getMessage() . "\n\n";
            return;
        }
    
        // Test 4: Test initializing the facade with error handling
        echo "Test 4: Testing Facade Initialization with Error Handling...\n";
        
        try {
            // Test normal initialization
            $workers->init();
            echo "✅ Workers facade initialized successfully\n";
            
            // Test initialization with missing components (should handle gracefully)
            $incomplete_workers = new AI_FAQ_Workers();
            // Deliberately remove a component to test error handling
            $reflection = new ReflectionClass($incomplete_workers);
            $property = $reflection->getProperty('security');
            $property->setAccessible(true);
            $property->setValue($incomplete_workers, null);
            
            $incomplete_workers->init();
            echo "✅ Workers facade handled missing components gracefully\n\n";
        } catch ( Exception $e ) {
            echo "❌ Failed during initialization error handling test: " . $e->getMessage() . "\n\n";
            return;
        }

    // Test 5: Test worker methods
    echo "Test 5: Testing Worker Methods...\n";
    
    try {
        // Get worker status
        $status = $workers->get_worker_status();
        echo "✅ get_worker_status() method works\n";
        
        // Test rate limiter
        $is_limited = $workers->is_rate_limited( 'test_worker' );
        echo "✅ is_rate_limited() method works\n";
        
        // Test analytics
        $analytics_data = $workers->get_analytics_data( 7 );
        echo "✅ get_analytics_data() method works\n";
        
        echo "All methods tested successfully\n\n";
    } catch ( Exception $e ) {
        echo "❌ Failed to test methods: " . $e->getMessage() . "\n\n";
        return;
    }
    
    echo "======================================\n";
    echo "All tests completed successfully!\n";
    echo "======================================\n";
}

// Run the tests
ai_faq_run_component_tests();