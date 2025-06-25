<?php
/**
 * Admin documentation management class for 365i AI FAQ Generator.
 * 
 * This class handles documentation pages, setup guides, troubleshooting,
 * and API reference display.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Admin
 * @since 2.1.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin documentation management class.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Admin_Documentation {

	/**
	 * Initialize the documentation component.
	 * 
	 * Set up hooks for AJAX handling and page routing.
	 * 
	 * @since 2.1.0
	 */
	public function init() {
		// Add AJAX handlers for documentation requests.
		add_action( 'wp_ajax_ai_faq_get_documentation', array( $this, 'handle_documentation_request' ) );
		add_action( 'wp_ajax_nopriv_ai_faq_get_documentation', array( $this, 'handle_documentation_request' ) );
		
		// Enqueue admin assets for documentation modal.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin assets for documentation modal.
	 *
	 * @since 2.1.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Load on ALL plugin admin pages by checking if we're on any plugin page
		// More robust approach that works regardless of exact hook suffix format
		if ( ! $this->is_plugin_admin_page() ) {
			return;
		}
		
		// Enqueue documentation modal CSS.
		wp_enqueue_style(
			'ai-faq-documentation-modal',
			AI_FAQ_GEN_URL . 'assets/css/documentation-modal.css',
			array(),
			AI_FAQ_GEN_VERSION
		);
		
		// Enqueue documentation modal JavaScript.
		wp_enqueue_script(
			'ai-faq-documentation-modal',
			AI_FAQ_GEN_URL . 'assets/js/documentation-modal.js',
			array( 'jquery' ),
			AI_FAQ_GEN_VERSION,
			true
		);
		
		// Localize script with AJAX data.
		wp_localize_script(
			'ai-faq-documentation-modal',
			'ai_faq_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'documentation_nonce' => wp_create_nonce( 'ai_faq_documentation_nonce' ),
				'strings' => array(
					'loading' => __( 'Loading documentation...', '365i-ai-faq-generator' ),
					'error' => __( 'Failed to load documentation. Please try again.', '365i-ai-faq-generator' ),
					'close' => __( 'Close', '365i-ai-faq-generator' ),
					'print' => __( 'Print', '365i-ai-faq-generator' ),
				),
			)
		);
	}

	/**
	 * Handle AJAX documentation requests.
	 * 
	 * @since 2.1.0
	 */
	public function handle_documentation_request() {
		// Verify nonce for security.
		if ( ! check_ajax_referer( 'ai_faq_documentation_nonce', 'nonce', false ) ) {
			wp_die( __( 'Security check failed.', '365i-ai-faq-generator' ), 403 );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', '365i-ai-faq-generator' ), 403 );
		}

		// Get and sanitize the documentation type.
		$doc_type = sanitize_text_field( wp_unslash( $_POST['doc_type'] ?? '' ) );

		// Get documentation content based on type.
		switch ( $doc_type ) {
			case 'setup_guide':
				$content = $this->get_setup_guide_content();
				break;
			case 'troubleshooting':
				$content = $this->get_troubleshooting_content();
				break;
			case 'api_reference':
				$content = $this->get_api_reference_content();
				break;
			default:
				wp_send_json_error( array(
					'message' => __( 'Invalid documentation type requested.', '365i-ai-faq-generator' ),
				) );
				return;
		}

		// Send successful response with content.
		wp_send_json_success( array(
			'content' => $content,
			'doc_type' => $doc_type,
		) );
	}

	/**
	 * Get worker setup guide content.
	 * 
	 * @since 2.1.0
	 * @return array Setup guide content.
	 */
	private function get_setup_guide_content() {
		return array(
			'title' => __( 'Cloudflare Worker Setup Guide', '365i-ai-faq-generator' ),
			'sections' => array(
				array(
					'title' => __( '1. Prerequisites', '365i-ai-faq-generator' ),
					'content' => __( 'Before setting up your workers, ensure you have:', '365i-ai-faq-generator' ),
					'items' => array(
						__( 'A Cloudflare account with Workers subscription (free tier available)', '365i-ai-faq-generator' ),
						__( 'Cloudflare API token with Workers permissions', '365i-ai-faq-generator' ),
						__( 'Your Cloudflare Account ID', '365i-ai-faq-generator' ),
						__( 'Basic understanding of Cloudflare Workers and AI API', '365i-ai-faq-generator' ),
					),
				),
				array(
					'title' => __( '2. Creating Your API Token', '365i-ai-faq-generator' ),
					'content' => __( 'Follow these steps to create a proper API token:', '365i-ai-faq-generator' ),
					'items' => array(
						__( 'Log into your Cloudflare dashboard', '365i-ai-faq-generator' ),
						__( 'Go to "My Profile" → "API Tokens"', '365i-ai-faq-generator' ),
						__( 'Click "Create Token" and select "Custom token"', '365i-ai-faq-generator' ),
						__( 'Set permissions: Zone:Zone:Read, Zone:Zone Settings:Edit, Account:Cloudflare Workers:Edit', '365i-ai-faq-generator' ),
						__( 'Add account resource: Include → All accounts', '365i-ai-faq-generator' ),
						__( 'Add zone resource: Include → All zones (if using custom domains)', '365i-ai-faq-generator' ),
						__( 'Click "Continue to summary" and "Create Token"', '365i-ai-faq-generator' ),
						__( 'Copy and securely store your API token', '365i-ai-faq-generator' ),
					),
				),
				array(
					'title' => __( '3. Worker Deployment', '365i-ai-faq-generator' ),
					'content' => __( 'Deploy each worker to your Cloudflare account:', '365i-ai-faq-generator' ),
					'items' => array(
						__( 'Question Generator Worker: Generates contextual questions with duplicate prevention', '365i-ai-faq-generator' ),
						__( 'Answer Generator Worker: Creates comprehensive answers with tone control', '365i-ai-faq-generator' ),
						__( 'FAQ Enhancer Worker: Improves existing FAQ content with SEO optimization', '365i-ai-faq-generator' ),
						__( 'SEO Analyzer Worker: Analyzes content for Position Zero targeting', '365i-ai-faq-generator' ),
						__( 'FAQ Extractor Worker: Extracts FAQ schema from websites', '365i-ai-faq-generator' ),
					),
				),
				array(
					'title' => __( '4. KV Namespace Configuration', '365i-ai-faq-generator' ),
					'content' => __( 'Create and configure KV namespaces for data storage:', '365i-ai-faq-generator' ),
					'items' => array(
						__( 'Create a KV namespace called "ai-faq-storage"', '365i-ai-faq-generator' ),
						__( 'Bind the namespace to all workers with variable name "AI_FAQ_KV"', '365i-ai-faq-generator' ),
						__( 'Configure rate limiting storage with namespace "rate-limits"', '365i-ai-faq-generator' ),
						__( 'Set up IP management storage with namespace "ip-management"', '365i-ai-faq-generator' ),
						__( 'Test KV connectivity using the worker test functions', '365i-ai-faq-generator' ),
					),
				),
				array(
					'title' => __( '5. Worker URL Configuration', '365i-ai-faq-generator' ),
					'content' => __( 'Configure worker URLs in the plugin settings:', '365i-ai-faq-generator' ),
					'items' => array(
						__( 'Use the format: https://worker-name.your-subdomain.workers.dev', '365i-ai-faq-generator' ),
						__( 'Or custom domain: https://api.yourdomain.com/worker-endpoint', '365i-ai-faq-generator' ),
						__( 'Ensure all URLs use HTTPS for security', '365i-ai-faq-generator' ),
						__( 'Test each worker connection using the "Test Connection" buttons', '365i-ai-faq-generator' ),
						__( 'Verify rate limiting is working by checking usage counters', '365i-ai-faq-generator' ),
					),
				),
				array(
					'title' => __( '6. Environment Variables', '365i-ai-faq-generator' ),
					'content' => __( 'Configure required environment variables for each worker:', '365i-ai-faq-generator' ),
					'items' => array(
						__( 'AI_API_TOKEN: Your Cloudflare AI API token', '365i-ai-faq-generator' ),
						__( 'RATE_LIMIT_KV: Binding to rate limiting KV namespace', '365i-ai-faq-generator' ),
						__( 'IP_MANAGEMENT_KV: Binding to IP management KV namespace', '365i-ai-faq-generator' ),
						__( 'ALLOWED_ORIGINS: Comma-separated list of allowed origins', '365i-ai-faq-generator' ),
						__( 'DEBUG_MODE: Set to "true" for detailed logging (development only)', '365i-ai-faq-generator' ),
					),
				),
				array(
					'title' => __( '7. Testing and Validation', '365i-ai-faq-generator' ),
					'content' => __( 'Validate your setup with comprehensive testing:', '365i-ai-faq-generator' ),
					'items' => array(
						__( 'Use the "Test Connection" button for each worker', '365i-ai-faq-generator' ),
						__( 'Run "Test All Workers" to validate the complete setup', '365i-ai-faq-generator' ),
						__( 'Check the Analytics page for successful API calls', '365i-ai-faq-generator' ),
						__( 'Verify rate limiting is tracking requests correctly', '365i-ai-faq-generator' ),
						__( 'Test IP management and blocking functionality', '365i-ai-faq-generator' ),
						__( 'Monitor Cloudflare logs for any errors or issues', '365i-ai-faq-generator' ),
					),
				),
			),
		);
	}

	/**
	 * Get troubleshooting content.
	 * 
	 * @since 2.1.0
	 * @return array Troubleshooting content.
	 */
	private function get_troubleshooting_content() {
		return array(
			'title' => __( 'Troubleshooting Guide', '365i-ai-faq-generator' ),
			'sections' => array(
				array(
					'title' => __( 'Common Connection Issues', '365i-ai-faq-generator' ),
					'content' => __( 'Resolve frequent worker connectivity problems:', '365i-ai-faq-generator' ),
					'problems' => array(
						array(
							'problem' => __( 'Worker Test Returns "Connection Failed"', '365i-ai-faq-generator' ),
							'solutions' => array(
								__( 'Verify the worker URL is correct and uses HTTPS', '365i-ai-faq-generator' ),
								__( 'Check that the worker is deployed and active in Cloudflare', '365i-ai-faq-generator' ),
								__( 'Ensure your server can make outbound HTTPS requests', '365i-ai-faq-generator' ),
								__( 'Verify firewall rules allow connections to Cloudflare', '365i-ai-faq-generator' ),
								__( 'Test the worker URL directly in a browser', '365i-ai-faq-generator' ),
							),
						),
						array(
							'problem' => __( 'HTTP 401 Unauthorized Errors', '365i-ai-faq-generator' ),
							'solutions' => array(
								__( 'Check your Cloudflare API token has correct permissions', '365i-ai-faq-generator' ),
								__( 'Verify the API token is not expired', '365i-ai-faq-generator' ),
								__( 'Ensure the token includes Workers:Edit permissions', '365i-ai-faq-generator' ),
								__( 'Regenerate the API token if necessary', '365i-ai-faq-generator' ),
								__( 'Check Account ID matches your Cloudflare account', '365i-ai-faq-generator' ),
							),
						),
						array(
							'problem' => __( 'HTTP 404 Not Found Errors', '365i-ai-faq-generator' ),
							'solutions' => array(
								__( 'Verify the worker name and URL path are correct', '365i-ai-faq-generator' ),
								__( 'Check the worker is deployed to the correct subdomain', '365i-ai-faq-generator' ),
								__( 'Ensure custom domain routing is properly configured', '365i-ai-faq-generator' ),
								__( 'Confirm the worker script is not returning 404 internally', '365i-ai-faq-generator' ),
								__( 'Check Cloudflare logs for deployment issues', '365i-ai-faq-generator' ),
							),
						),
						array(
							'problem' => __( 'HTTP 429 Rate Limit Exceeded', '365i-ai-faq-generator' ),
							'solutions' => array(
								__( 'Wait for the rate limit window to reset (typically 1 hour)', '365i-ai-faq-generator' ),
								__( 'Increase rate limits in the Rate Limiting configuration', '365i-ai-faq-generator' ),
								__( 'Check for excessive automated requests', '365i-ai-faq-generator' ),
								__( 'Review IP management for blocked addresses', '365i-ai-faq-generator' ),
								__( 'Consider upgrading Cloudflare plan for higher limits', '365i-ai-faq-generator' ),
							),
						),
					),
				),
				array(
					'title' => __( 'KV Storage Issues', '365i-ai-faq-generator' ),
					'content' => __( 'Resolve KV namespace and storage problems:', '365i-ai-faq-generator' ),
					'problems' => array(
						array(
							'problem' => __( 'KV Namespace Not Found', '365i-ai-faq-generator' ),
							'solutions' => array(
								__( 'Create the required KV namespaces in Cloudflare dashboard', '365i-ai-faq-generator' ),
								__( 'Bind namespaces to workers with correct variable names', '365i-ai-faq-generator' ),
								__( 'Redeploy workers after adding KV bindings', '365i-ai-faq-generator' ),
								__( 'Check namespace names match worker expectations', '365i-ai-faq-generator' ),
								__( 'Verify account permissions include KV access', '365i-ai-faq-generator' ),
							),
						),
						array(
							'problem' => __( 'Configuration Data Not Persisting', '365i-ai-faq-generator' ),
							'solutions' => array(
								__( 'Check KV write permissions and quotas', '365i-ai-faq-generator' ),
								__( 'Verify KV eventual consistency (data may take time to propagate)', '365i-ai-faq-generator' ),
								__( 'Test KV operations using Cloudflare dashboard', '365i-ai-faq-generator' ),
								__( 'Check for KV storage limits and usage', '365i-ai-faq-generator' ),
								__( 'Review worker logs for KV operation errors', '365i-ai-faq-generator' ),
							),
						),
					),
				),
				array(
					'title' => __( 'Performance Issues', '365i-ai-faq-generator' ),
					'content' => __( 'Optimize worker performance and response times:', '365i-ai-faq-generator' ),
					'problems' => array(
						array(
							'problem' => __( 'Slow Response Times', '365i-ai-faq-generator' ),
							'solutions' => array(
								__( 'Check Cloudflare edge location performance', '365i-ai-faq-generator' ),
								__( 'Optimize worker code for faster execution', '365i-ai-faq-generator' ),
								__( 'Review AI model selection for speed vs accuracy trade-offs', '365i-ai-faq-generator' ),
								__( 'Monitor CPU time usage in worker metrics', '365i-ai-faq-generator' ),
								__( 'Consider caching frequently requested content', '365i-ai-faq-generator' ),
							),
						),
						array(
							'problem' => __( 'Timeout Errors', '365i-ai-faq-generator' ),
							'solutions' => array(
								__( 'Increase timeout values in plugin settings', '365i-ai-faq-generator' ),
								__( 'Optimize worker processing time', '365i-ai-faq-generator' ),
								__( 'Check for AI API timeout issues', '365i-ai-faq-generator' ),
								__( 'Review network connectivity and latency', '365i-ai-faq-generator' ),
								__( 'Consider breaking large requests into smaller chunks', '365i-ai-faq-generator' ),
							),
						),
					),
				),
				array(
					'title' => __( 'Debugging Tools', '365i-ai-faq-generator' ),
					'content' => __( 'Use these tools to diagnose issues:', '365i-ai-faq-generator' ),
					'items' => array(
						__( 'Worker Test Buttons: Test individual worker connectivity', '365i-ai-faq-generator' ),
						__( 'Analytics Dashboard: Monitor success rates and performance', '365i-ai-faq-generator' ),
						__( 'Cloudflare Logs: Review detailed request and error logs', '365i-ai-faq-generator' ),
						__( 'WordPress Debug Log: Check for PHP errors and warnings', '365i-ai-faq-generator' ),
						__( 'Browser Developer Tools: Inspect network requests and responses', '365i-ai-faq-generator' ),
						__( 'Rate Limiting Page: Monitor usage and violations', '365i-ai-faq-generator' ),
						__( 'IP Management: Check for blocked or flagged addresses', '365i-ai-faq-generator' ),
					),
				),
			),
		);
	}

	/**
	 * Get API reference content.
	 * 
	 * @since 2.1.0
	 * @return array API reference content.
	 */
	private function get_api_reference_content() {
		return array(
			'title' => __( 'API Reference Documentation', '365i-ai-faq-generator' ),
			'sections' => array(
				array(
					'title' => __( 'Authentication', '365i-ai-faq-generator' ),
					'content' => __( 'All API requests require proper authentication:', '365i-ai-faq-generator' ),
					'code' => array(
						'headers' => array(
							'Authorization' => 'Bearer YOUR_API_TOKEN',
							'Content-Type' => 'application/json',
							'X-Request-ID' => 'unique-request-identifier',
						),
					),
					'items' => array(
						__( 'Use your Cloudflare API token in the Authorization header', '365i-ai-faq-generator' ),
						__( 'Include Content-Type: application/json for POST requests', '365i-ai-faq-generator' ),
						__( 'Add X-Request-ID for request tracking and debugging', '365i-ai-faq-generator' ),
						__( 'Ensure HTTPS is used for all requests', '365i-ai-faq-generator' ),
					),
				),
				array(
					'title' => __( 'Question Generator Worker', '365i-ai-faq-generator' ),
					'content' => __( 'Generate contextual questions with duplicate prevention:', '365i-ai-faq-generator' ),
					'endpoint' => 'POST /question-generator',
					'request' => array(
						'question' => 'What is artificial intelligence?',
						'context' => 'Technology and AI overview',
						'mode' => 'generate',
						'max_questions' => 5,
						'style' => 'professional',
					),
					'response' => array(
						'success' => true,
						'questions' => array(
							'What are the main types of artificial intelligence?',
							'How does machine learning relate to AI?',
							'What are practical applications of AI technology?',
						),
						'processing_time' => '2.34s',
						'model_used' => '@cf/meta/llama-3.1-8b-instruct',
					),
				),
				array(
					'title' => __( 'Answer Generator Worker', '365i-ai-faq-generator' ),
					'content' => __( 'Create comprehensive answers with tone control:', '365i-ai-faq-generator' ),
					'endpoint' => 'POST /answer-generator',
					'request' => array(
						'question' => 'What is artificial intelligence?',
						'context' => 'Beginner-friendly explanation',
						'tone' => 'friendly',
						'length' => 'medium',
						'include_examples' => true,
					),
					'response' => array(
						'success' => true,
						'answer' => 'Artificial Intelligence (AI) is technology that enables machines to perform tasks that typically require human intelligence...',
						'word_count' => 150,
						'tone_score' => 0.85,
						'processing_time' => '3.12s',
					),
				),
				array(
					'title' => __( 'FAQ Enhancer Worker', '365i-ai-faq-generator' ),
					'content' => __( 'Improve existing FAQ content with SEO optimization:', '365i-ai-faq-generator' ),
					'endpoint' => 'POST /faq-enhancer',
					'request' => array(
						'faq' => array(
							array(
								'question' => 'What is AI?',
								'answer' => 'AI is smart technology.',
							),
						),
						'mode' => 'enhance',
						'target_keywords' => array( 'artificial intelligence', 'machine learning' ),
						'seo_optimize' => true,
					),
					'response' => array(
						'success' => true,
						'enhanced_faq' => array(
							array(
								'question' => 'What is Artificial Intelligence (AI) and How Does It Work?',
								'answer' => 'Artificial Intelligence (AI) is advanced technology that enables machines and computers to simulate human intelligence...',
								'seo_score' => 92,
								'keyword_density' => 0.035,
							),
						),
						'improvements' => array(
							'Added target keywords naturally',
							'Improved question clarity and SEO potential',
							'Enhanced answer comprehensiveness',
						),
					),
				),
				array(
					'title' => __( 'SEO Analyzer Worker', '365i-ai-faq-generator' ),
					'content' => __( 'Analyze content for SEO optimization and Position Zero targeting:', '365i-ai-faq-generator' ),
					'endpoint' => 'POST /seo-analyzer',
					'request' => array(
						'content' => 'What is artificial intelligence? AI is technology that makes machines smart.',
						'target_keywords' => array( 'artificial intelligence', 'AI technology' ),
						'analysis_type' => 'comprehensive',
					),
					'response' => array(
						'success' => true,
						'seo_score' => 75,
						'analysis' => array(
							'keyword_density' => 0.08,
							'readability_score' => 82,
							'position_zero_potential' => 'High',
							'content_length' => 'Optimal',
							'semantic_richness' => 'Good',
						),
						'recommendations' => array(
							'Add more semantic keywords',
							'Improve content structure with subheadings',
							'Include related questions',
						),
					),
				),
				array(
					'title' => __( 'FAQ Extractor Worker', '365i-ai-faq-generator' ),
					'content' => __( 'Extract existing FAQ schema from websites:', '365i-ai-faq-generator' ),
					'endpoint' => 'POST /faq-extractor',
					'request' => array(
						'url' => 'https://example.com/faq',
						'mode' => 'extract',
						'schema_types' => array( 'JSON-LD', 'Microdata', 'RDFa' ),
						'include_metadata' => true,
					),
					'response' => array(
						'success' => true,
						'faqs_found' => 12,
						'schema_types' => array( 'JSON-LD' ),
						'faqs' => array(
							array(
								'question' => 'How do I create an account?',
								'answer' => 'To create an account, click the Sign Up button...',
								'schema_type' => 'JSON-LD',
							),
						),
						'metadata' => array(
							'page_title' => 'Frequently Asked Questions',
							'extraction_time' => '1.85s',
						),
					),
				),
				array(
					'title' => __( 'Rate Limiting', '365i-ai-faq-generator' ),
					'content' => __( 'All endpoints implement rate limiting to prevent abuse:', '365i-ai-faq-generator' ),
					'items' => array(
						__( 'Default: 50 requests per hour per IP address', '365i-ai-faq-generator' ),
						__( 'Configurable limits for each worker type', '365i-ai-faq-generator' ),
						__( 'HTTP 429 response when limits exceeded', '365i-ai-faq-generator' ),
						__( 'X-RateLimit-* headers in responses', '365i-ai-faq-generator' ),
						__( 'Automatic reset every hour', '365i-ai-faq-generator' ),
					),
					'headers' => array(
						'X-RateLimit-Limit' => '50',
						'X-RateLimit-Remaining' => '47',
						'X-RateLimit-Reset' => '1640995200',
					),
				),
				array(
					'title' => __( 'Error Handling', '365i-ai-faq-generator' ),
					'content' => __( 'Standard error response format for all endpoints:', '365i-ai-faq-generator' ),
					'error_response' => array(
						'success' => false,
						'error' => array(
							'code' => 'INVALID_REQUEST',
							'message' => 'Missing required parameter: question',
							'details' => array(
								'parameter' => 'question',
								'expected_type' => 'string',
							),
						),
						'request_id' => 'req_123456789',
					),
					'error_codes' => array(
						'INVALID_REQUEST' => 'Request format or parameters invalid',
						'RATE_LIMIT_EXCEEDED' => 'Too many requests from this IP',
						'UNAUTHORIZED' => 'Invalid or missing API token',
						'INTERNAL_ERROR' => 'Worker processing error',
						'AI_MODEL_ERROR' => 'AI service unavailable',
					),
				),
			),
		);
	}

	/**
		* Check if we're on a plugin admin page.
		*
		* @since 2.1.0
		* @return bool True if on plugin admin page, false otherwise.
		*/
	private function is_plugin_admin_page() {
		// Check if we're in admin area first
		if ( ! is_admin() ) {
			return false;
		}

		// Get current screen
		$current_screen = get_current_screen();
		if ( ! $current_screen ) {
			return false;
		}

		// Check if current page is one of our plugin pages
		// Look for 'ai-faq-generator' in the page parameter
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		
		// Return true if page starts with our plugin prefix
		return strpos( $page, 'ai-faq-generator' ) === 0;
	}

	/**
		* Get documentation URLs for the help buttons.
	 * 
	 * @since 2.1.0
	 * @return array Documentation URLs.
	 */
	public static function get_documentation_urls() {
		return array(
			'setup_guide' => add_query_arg( array(
				'action' => 'ai_faq_get_documentation',
				'doc_type' => 'setup_guide',
				'nonce' => wp_create_nonce( 'ai_faq_documentation_nonce' ),
			), admin_url( 'admin-ajax.php' ) ),
			'troubleshooting' => add_query_arg( array(
				'action' => 'ai_faq_get_documentation',
				'doc_type' => 'troubleshooting',
				'nonce' => wp_create_nonce( 'ai_faq_documentation_nonce' ),
			), admin_url( 'admin-ajax.php' ) ),
			'api_reference' => add_query_arg( array(
				'action' => 'ai_faq_get_documentation',
				'doc_type' => 'api_reference',
				'nonce' => wp_create_nonce( 'ai_faq_documentation_nonce' ),
			), admin_url( 'admin-ajax.php' ) ),
		);
	}
}