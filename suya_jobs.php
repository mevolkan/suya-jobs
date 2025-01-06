<?php
/*
    Plugin Name: Suya jobs
    Plugin URI: http://volkan.co.ke
    Description: This plugin displays Jobs and other opportunities
    Author: Volkan
    Version: 1.0
    Author URI: http://volkan.co.ke
    License: GPL-2.0+
    License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

if (!defined('ABSPATH')) {
    exit;
    }

define('SUYA_JOBS_VERSION', '1.0.0');


/**
 * Class Suya_Jobs
 * Handles job posting functionality including custom post types, meta boxes, and shortcodes
 */
class Suya_Jobs
    {
    /**
     * @var Suya_Jobs|null Singleton instance
     */
    private static $instance = null;

    // Add new property for blocks
    private $blocks_dir;

    /**
     * @var string Plugin version
     */
    private const VERSION = '1.0.0'; // Define version constant

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
        {
        // Set blocks directory path
        $this->blocks_dir = plugin_dir_path(__FILE__) . 'blocks/';

        $this->init_hooks();
        }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks()
        {
        add_action('init', [$this, 'register_jobs_post_type']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('add_meta_boxes', [$this, 'register_jobs_meta_boxes']);
        add_action('save_post_job', [$this, 'save_jobs_meta'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('template_include', [$this, 'custom_template_include']);


        // Block editor related hooks
        add_action('init', [$this, 'register_meta_fields']);
        add_action('init', [$this, 'register_blocks']);
        add_action('admin_enqueue_scripts', [$this, 'register_admin_scripts']);

        // Styles
        add_action('wp_enqueue_scripts', [$this, 'suya_jobs_enqueue_styles']);

        }
    /**
     * Register meta fields for block editor
     */
    public function register_meta_fields()
        {
        register_post_meta('job', '_close_date', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function () {
                return current_user_can('edit_posts');
                }
        ]);

        register_post_meta('job', '_location', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function () {
                return current_user_can('edit_posts');
                }
        ]);

        register_post_meta('job', '_job_type', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function () {
                return current_user_can('edit_posts');
                }
        ]);
        }

    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets()
        {
        wp_enqueue_script(
            'suya-jobs-block-editor',
            plugin_dir_url(__FILE__) . 'js/block-editor.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor'],
            self::VERSION,
            true
        );
        add_action('init', [$this, 'register_job_elementor_locations']);
        add_action('elementor/theme/register_conditions', [$this, 'add_job_template_conditions']);
        }
    /**
     * Register blocks without scripts
     */
    public function register_blocks()
        {
        if (!file_exists($this->blocks_dir)) {
            wp_mkdir_p($this->blocks_dir);
            chmod($this->blocks_dir, 0755);
            }

        register_block_type('suya-jobs/job-application-form', array(
            'editor_script' => 'suya-jobs-form-block',
        ));

        // Add block shortcode support
        add_filter('render_block', array($this, 'process_form_block'), 10, 2);
        }

    /**
     * Register and enqueue admin scripts at the correct time
     */
    public function register_admin_scripts($hook)
        {
        // Only register on post edit screens
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
            }

        // Get post type
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'job') {
            return;
            }

        // Register block editor assets
        wp_register_script(
            'suya-jobs-block-editor',
            plugin_dir_url(__FILE__) . 'js/block-editor.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor'],
            self::VERSION,
            true
        );
        wp_enqueue_script('suya-jobs-block-editor');

        // Register form block script
        wp_register_script(
            'suya-jobs-form-block',
            plugin_dir_url(__FILE__) . 'blocks/job-application-form/build/index.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data'),
            filemtime(plugin_dir_path(__FILE__) . 'blocks/job-application-form/build/index.js'),
            true
        );
        wp_enqueue_script('suya-jobs-form-block');
        }



    /**
     * Register shortcodes
     */
    public function register_shortcodes(): void
        {
        add_shortcode('suya_opportunities', [$this, 'display_suya_opportunities']);
        add_shortcode('suya_jobs', [$this, 'display_suya_jobs']);
        }

    /**
     * Register the Jobs custom post type
     */
    public function register_jobs_post_type(): void
        {
        $labels = [
            'name' => __('Jobs', 'suya-jobs'),
            'singular_name' => __('Job', 'suya-jobs'),
            'menu_name' => __('Jobs', 'suya-jobs'),
            'add_new' => __('Add New', 'suya-jobs'),
            'add_new_item' => __('Add New Job', 'suya-jobs'),
            'edit_item' => __('Edit Job', 'suya-jobs'),
            'new_item' => __('New Job', 'suya-jobs'),
            'view_item' => __('View Job', 'suya-jobs'),
            'search_items' => __('Search Jobs', 'suya-jobs'),
            'not_found' => __('No jobs found', 'suya-jobs'),
            'not_found_in_trash' => __('No jobs found in Trash', 'suya-jobs'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'job'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => ['title', 'editor'],
            'show_in_rest' => true,
        ];

        register_post_type('job', $args);

        // Register Job Type Taxonomy
        register_taxonomy('job_type', 'job', [
            'hierarchical' => false,
            'labels' => [
                'name' => __('Job Types', 'suya-jobs'),
                'singular_name' => __('Job Type', 'suya-jobs'),
            ],
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'job-type'],
            'show_in_rest' => true,
        ]);
        }

    /**
     * Register meta boxes
     */
    public function register_jobs_meta_boxes(): void
        {
        add_meta_box(
            'job_details',
            __('Job Details', 'suya-jobs'),
            [$this, 'render_jobs_meta_box'],
            'job',
            'normal',
            'high'
        );

        add_meta_box(
            'job_type_meta',
            __('Job Type', 'suya-jobs'),
            [$this, 'render_job_type_meta_box'],
            'job',
            'side'
        );
        }

    /**
     * Render job details meta box
     */
    public function render_jobs_meta_box($post): void
        {
        wp_nonce_field('jobs_meta_box', 'jobs_meta_box_nonce');

        $close_date = get_post_meta($post->ID, '_close_date', true);
        $location = get_post_meta($post->ID, '_location', true);
        $download_id = get_post_meta($post->ID, '_download', true);
        ?>
        <p>
            <label for="close_date"><?php esc_html_e('Close Date:', 'suya-jobs'); ?></label>
            <input type="date" id="close_date" name="close_date" value="<?php echo esc_attr($close_date); ?>" required>
        </p>

        <p>
            <label for="location"><?php esc_html_e('Location:', 'suya-jobs'); ?></label>
            <input type="text" id="location" name="location" value="<?php echo esc_attr($location); ?>" maxlength="255">
        </p>

        <p>
            <label for="download"><?php esc_html_e('Download:', 'suya-jobs'); ?></label>
            <?php
            $download_url = wp_get_attachment_url($download_id);
            if ($download_url) {
                echo '<br><a href="' . esc_url($download_url) . '">' . esc_html__('Current file', 'suya-jobs') . '</a>';
                }
            ?>
            <input type="file" id="download" name="download" accept=".pdf,.doc">
        </p>
        <?php
        }

    /**
     * Render job type meta box
     */
    public function render_job_type_meta_box($post): void
        {
        wp_nonce_field('job_type_meta_box', 'job_type_meta_box_nonce');

        $current_type = get_post_meta($post->ID, '_job_type', true);
        $job_types = $this->get_job_types();

        foreach ($job_types as $value => $label) {
            ?>
            <p>
                <input type="radio" id="job_type_<?php echo esc_attr($value); ?>" name="job_type"
                    value="<?php echo esc_attr($value); ?>" <?php checked($current_type, $value); ?>>
                <label for="job_type_<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></label>
            </p>
            <?php
            }
        }
    /**
     * Get available job types
     */
    private function get_job_types(): array
        {
        return [
            'contract' => __('Contract', 'suya-jobs'),
            'freelance' => __('Freelance', 'suya-jobs'),
            'full_time' => __('Full Time', 'suya-jobs'),
            'part_time' => __('Part Time', 'suya-jobs'),
            'internship' => __('Internship', 'suya-jobs'),
            'temporary' => __('Temporary', 'suya-jobs'),
        ];
        }

    /**
     * Save job meta data
     */
    public function save_jobs_meta($post_id, $post): void
        {
        if (!$this->can_save_meta($post_id)) {
            return;
            }

        $this->save_text_meta($post_id, ['close_date', 'location', 'job_type']);
        $this->handle_file_upload($post_id);
        }

    /**
     * Check if meta can be saved
     */
    private function can_save_meta($post_id): bool
        {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
            }

        if (!current_user_can('edit_post', $post_id)) {
            return false;
            }

        if (
            !isset($_POST['jobs_meta_box_nonce'], $_POST['job_type_meta_box_nonce']) ||
            !wp_verify_nonce(wp_unslash($_POST['jobs_meta_box_nonce']), 'jobs_meta_box') ||
            !wp_verify_nonce(wp_unslash($_POST['job_type_meta_box_nonce']), 'job_type_meta_box')
        ) {
            return false;
            }

        return true;
        }

    /**
     * Save text meta fields
     */
    private function save_text_meta($post_id, array $fields): void
        {
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, "_{$field}", sanitize_text_field(wp_unslash($_POST[$field])));
                }
            }
        }

    /**
     * Handle file upload
     */
    private function handle_file_upload($post_id): void
        {
        if (empty($_FILES['download']['name'])) {
            return;
            }

        $allowed_types = ['application/pdf', 'application/msword'];
        if (isset($_FILES['download']['type'])) {
            $file_type = $_FILES['download']['type'];
            }


        if (!in_array($file_type, $allowed_types, true)) {
            return;
            }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('download', $post_id);
        if (!is_wp_error($attachment_id)) {
            update_post_meta($post_id, '_download', $attachment_id);
            }
        }

    /**
     * Enqueue assets
     */
    public function enqueue_assets(): void
        {
        if ($this->should_load_assets()) {
            wp_enqueue_style(
                'suya-styles',
                plugin_dir_url(__FILE__) . 'styles/style.css',
                [],
                self::VERSION
            );

            wp_enqueue_script(
                'suya-ajax-script',
                plugin_dir_url(__FILE__) . 'js/suya.js',
                ['jquery'],
                self::VERSION,
                true
            );

            wp_localize_script('suya-ajax-script', 'ajax_object', [
                'ajax_url' => admin_url('admin-ajax.php')
            ]);
            }

        }
    function suya_jobs_enqueue_styles()
        {
        $custom_css = "
                .download-button {
                    display: inline-block;
                    padding: 0.8rem 1.5rem;
                    background: #0073aa;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    transition: background 0.3s ease;
                }
                .download-button:hover {
                    background: #005177;
                    color: white;
                }
                       .job-single-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 2rem;
    }
    .job-meta {
        background: #f5f5f5;
        padding: 1.5rem;
        margin: 1.5rem 0;
        border-radius: 4px;
    }
    .job-meta > div {
        margin-bottom: 0.5rem;
    }
    .job-meta > div:last-child {
        margin-bottom: 0;
    }
    .job-download {
        margin-top: 2rem;
    }
    .download-button {
        display: inline-block;
        padding: 0.8rem 1.5rem;
        background: #0073aa;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background 0.3s ease;
    }
    .download-button:hover {
        background: #005177;
        color: white;
    }
            ";
        wp_add_inline_style('wp-block-library', $custom_css); // For block themes
        wp_add_inline_style('classic-theme-styles', $custom_css); // For classic themes
        wp_add_inline_style('elementor-frontend', $custom_css); // For Elementor themes
        }

    /**
     * Check if assets should be loaded
     */
    private function should_load_assets(): bool
        {
        return is_singular(['job']) ||
            $this->has_any_shortcode(['suya_jobs']);
        }



    /**
     * Check if post has any of the specified shortcodes
     */
    private function has_any_shortcode(array $shortcodes): bool
        {
        $post = get_post();
        if (!$post) {
            return false;
            }

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
                }
            }
        return false;
        }

    /**
     * Include custom template
     */
    public function custom_template_include($template): string
        {
        $post_type = get_post_type();
        if (is_singular(['event', 'job', 'tender'])) {
            $custom_template = plugin_dir_path(__FILE__) . "includes/templates/single-{$post_type}.php";
            if (file_exists($custom_template)) {
                return $custom_template;
                }
            }
        return $template;
        }



    private function register_job_application_form_block()
        {
        // Register block script
        wp_register_script(
            'suya-jobs-form-block',
            plugins_url('blocks/job-application-form/index.js', __FILE__),
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data'),
            filemtime(plugin_dir_path(__FILE__) . 'blocks/job-application-form/index.js'),
            false
        );

        // Register block type
        register_block_type('suya-jobs/job-application-form', array(
            'editor_script' => 'suya-jobs-form-block',
        ));
        }

    /**
     * Process form block to handle shortcodes
     */
    public function process_form_block($block_content, $block)
        {
        if ($block['blockName'] === 'suya-jobs/job-application-form') {
            return do_shortcode($block_content);
            }
        return $block_content;
        }

    /**
     * Display opportunities shortcode
     */
    public function display_suya_opportunities(): string
        {
        return $this->display_items(['job'], 'Current Opportunities');
        }

    /**
     * Display jobs shortcode
     */
    public function display_suya_jobs(): string
        {
        return $this->display_items('job', 'Current Openings');
        }

    /**
     * Get opportunities query
     */
    private function get_opportunities($post_type): WP_Query
        {
        return new WP_Query([
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'meta_query' => [
                [
                    'key' => 'close_date',
                    'value' => gmdate('Y-m-d'),
                    'compare' => '>',
                    'type' => 'DATE',
                ],
            ],
        ]);
        }

    /**
     * Display items
     */
    private function display_items($post_type, string $title): string
        {
        $query = $this->get_opportunities($post_type);
        if (!$query->have_posts()) {
            return sprintf('<p>No %s available</p>', strtolower($title));
            }

        $output = sprintf(
            "<h2>%s</h2><div class='%s'>",
            esc_html($title),
            esc_attr(is_array($post_type) ? 'jobs' : $post_type . 's')
        );

        while ($query->have_posts()) {
            $query->the_post();
            $output .= $this->get_item_html(get_post_type());
            }
        wp_reset_postdata();

        return $output . '</div>';
        }

    /**
     * Get HTML for single item
     */
    private function get_item_html(string $post_type): string
        {
        $data = [
            'post_type' => $post_type,
            'permalink' => get_permalink(),
            'title' => get_the_title(),
            'close_date' => get_post_meta(get_the_ID(), 'close_date', true)
        ];

        if ($post_type === 'job') {
            $data['location'] = get_post_meta(get_the_ID(), 'location', true);
            } elseif ($post_type === 'tender') {
            $data['ref_number'] = get_post_meta(get_the_ID(), 'reference_number', true);
            $data['download_url'] = wp_get_attachment_url(get_post_meta(get_the_ID(), 'download', true));
            }

        return $this->render_template('item', $data);
        }

    /**
     * Render template with data
     */
    private function render_template(string $template, array $data): string
        {
        ob_start();
        include plugin_dir_path(__FILE__) . "templates/{$template}.php";
        return ob_get_clean();
        }

    /**
     * Get singleton instance
     */
    public static function get_instance(): self
        {
        if (self::$instance === null) {
            self::$instance = new self();
            }
        return self::$instance;
        }
    /**
     * Prevent cloning of singleton instance
     */
    private function __clone()
        {
        }

    /**
     * Prevent unserializing of singleton instance
     */
    public function __wakeup()
        {
        throw new \Exception("Cannot unserialize singleton");
        }
    }

// Initialize the plugin
Suya_Jobs::get_instance();