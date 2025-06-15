<?php
/**
 * The documentation-specific functionality of the plugin.
 *
 * @link       https://365i.com
 * @since      1.0.0
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/admin
 */

/**
 * The documentation-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * the admin documentation pages.
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/admin
 * @author     365i <info@365i.com>
 */
class FAQ_AI_Generator_Docs {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin documentation area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style('dashicons');
        wp_enqueue_style(
            $this->plugin_name . '-documentation',
            plugin_dir_url(__FILE__) . 'css/documentation.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the documentation menu page.
     *
     * @since    1.0.0
     */
    public function add_documentation_menu() {
        add_submenu_page(
            'faq-ai-generator',
            __('Documentation', 'faq-ai-generator'),
            __('Documentation', 'faq-ai-generator'),
            'manage_options',
            'faq-ai-generator-docs',
            array($this, 'display_documentation_page')
        );
    }

    /**
     * Display the documentation page.
     *
     * @since    1.0.0
     */
    public function display_documentation_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get the current doc to display
        $doc = isset($_GET['doc']) ? sanitize_text_field($_GET['doc']) : '';

        // Start the page output
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('FAQ AI Generator Documentation', 'faq-ai-generator') . '</h1>';

        // Display the appropriate documentation page
        switch ($doc) {
            case 'user-guide':
                $this->load_doc_template('user-guide.php');
                break;
            case 'shortcode-parameters':
                $this->load_doc_template('shortcode-parameters.php');
                break;
            case 'faq-schema-types':
                $this->load_doc_template('faq-schema-types.php');
                break;
            default:
                $this->load_doc_template('documentation-menu.php');
                break;
        }

        echo '</div>';
    }

    /**
     * Load a documentation template file.
     *
     * @since    1.0.0
     * @param    string    $template    The template file to load.
     */
    private function load_doc_template($template) {
        $template_path = plugin_dir_path(__FILE__) . 'docs/' . $template;
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="notice notice-error"><p>' . 
                 esc_html__('Documentation file not found.', 'faq-ai-generator') . 
                 '</p></div>';
            $this->load_doc_template('documentation-menu.php');
        }
    }

    /**
     * Add a documentation link to the plugin action links.
     *
     * @since    1.0.0
     * @param    array    $links    The existing plugin action links.
     * @return   array              The modified plugin action links.
     */
    public function add_documentation_link($links) {
        $doc_link = '<a href="' . admin_url('admin.php?page=faq-ai-generator-docs') . '">' . 
                    __('Documentation', 'faq-ai-generator') . '</a>';
        array_unshift($links, $doc_link);
        return $links;
    }
}