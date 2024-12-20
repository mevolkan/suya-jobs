<?php
/*
    Plugin Name: Suya jobs plugin
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

    /**
     * @var string Plugin version
     */
    private const VERSION = '1.0.0'; // Define version constant

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
        {
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

        $this->render_meta_box_template('job-details', [
            'close_date' => $close_date,
            'location' => $location,
            'download_id' => $download_id
        ]);
        }

    /**
     * Render job type meta box
     */
    public function render_job_type_meta_box($post): void
        {
        wp_nonce_field('job_type_meta_box', 'job_type_meta_box_nonce');

        $current_type = get_post_meta($post->ID, '_job_type', true);
        $job_types = $this->get_job_types();

        $this->render_meta_box_template('job-type', [
            'current_type' => $current_type,
            'job_types' => $job_types
        ]);
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
            !wp_verify_nonce($_POST['jobs_meta_box_nonce'], 'jobs_meta_box') ||
            !wp_verify_nonce($_POST['job_type_meta_box_nonce'], 'job_type_meta_box')
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
                update_post_meta($post_id, "_{$field}", sanitize_text_field($_POST[$field]));
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
        $file_type = $_FILES['download']['type'];

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

        if ($this->should_load_map_assets()) {
            $this->enqueue_map_assets();
            }
        }

    /**
     * Check if assets should be loaded
     */
    private function should_load_assets(): bool
        {
        return is_singular(['job', 'tender', 'event']) ||
            $this->has_any_shortcode(['suya_opportunities', 'suya_jobs', 'suya_procurement', 'suya_events']);
        }

    /**
     * Check if map assets should be loaded
     */
    private function should_load_map_assets(): bool
        {
        return $this->has_any_shortcode(['suya_projects']);
        }

    /**
     * Enqueue map-specific assets
     */
    private function enqueue_map_assets(): void
        {
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', []);
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.3/dist/leaflet.js', ['jquery']);
        wp_enqueue_script(
            'suya-projects-script',
            plugin_dir_url(__FILE__) . 'js/map.js',
            ['jquery'],
            self::VERSION,
            true
        );
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
            'posts_per_page' => -1,
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