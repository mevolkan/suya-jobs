<?php
/*
    Plugin Name: Reconcile EA
    Plugin URI: http://volkan.co.ke
    Description:This is plugin displays Jobs among other things
    Author: Volkan
    Version: 1.0
    Author URI: http://volkan.co.ke
    License:GPL-2.0+
    License URI:http://www.gnu.org/licenses/gpl-2.0.txt

 */

if (!defined('ABSPATH')) {
    exit;
    }

if (!defined('Reconcile_EA')) {
    define('Reconcile_EA', '1.0.0');
    }

class Reconcile_EA
    {

    /**
     * Static property to hold our singleton instance
     */
    public static $instance = false;

    /**
     * This is our constructor
     *
     * @return void
     */
    private function __construct()
        {
        add_shortcode('reconcile_opportunities', [$this, 'display_reconcile_opportunities']);
        add_shortcode('reconcile_jobs', [$this, 'display_reconcile_jobs']);
        add_shortcode('reconcile_procurement', [$this, 'display_reconcile_tenders']);

        // Hook into the 'template_include' filter to use custom template
        add_filter('template_include', [$this, 'custom_job_template_include']);
        add_filter('template_include', [$this, 'custom_tender_template_include']);
        }

    /**
     * If an instance exists, this returns it.  If not, it creates one and
     * retuns it.
     *
     * @return Reconcile_EA
     */
    public static function getInstance()
        {
        if (!self::$instance) {
            self::$instance = new self();
            }

        return self::$instance;
        }

    /**
     * load textdomain
     *
     * @return void
     */
    public function textdomain()
        {
        load_plugin_textdomain('reconcile-ea', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

    // Shortcode to display jobs and procurement notices
    public function display_reconcile_opportunities()
        {
        if (is_a($GLOBALS['post'], 'WP_Post') && stripos($GLOBALS['post']->post_content, '[reconcile_opportunities') !== false) {
            wp_enqueue_style('reconcile-jobs-css', plugin_dir_url(__FILE__) . 'styles/style.css', false);
            wp_enqueue_script('reconcile-jobs-ajax-script', plugin_dir_url(__FILE__) . 'js/reconcile-jobs-ajax.js', ['jquery'], null, true);
            wp_localize_script('reconcile-jobs-ajax-script', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
            }

        // Define the post type
        $post_type = array('job', 'tender');

        // Get current date in mm/dd/yyyy format
        $current_date = date('m/d/Y');

        // Convert current date to a format suitable for comparison (Y-m-d)
        $current_date_sql = date('Y-m-d', strtotime($current_date));

        // Query for all jobs with a close_date greater than the current date
        $query = new WP_Query([
            'post_type' => $post_type,
            'post_status' => ['publish'],
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'close_date',
                    'value' => $current_date_sql,
                    'compare' => '>',
                    'type' => 'DATE', // Ensure proper date comparison
                ],
            ],
        ]);

        // Check if there are posts
        if ($query->have_posts()) {
            $output = '<h2> Current Opportunities</h2>';
            $output .= '<div class="jobs">';

            // Loop through posts and display them
            while ($query->have_posts()) {
                $query->the_post();
                $output .= '<div class="single-job">';
                $output .= '<div class="details">
                    <a href="' . get_permalink() . '">' . get_the_title() . '</a>
                    <span>' . esc_html(get_post_meta(get_the_ID(), 'location', true)) . '</span>
                   <span>Closing: ' . esc_html(get_post_meta(get_the_ID(), 'close_date', true)) . '</span>
                    <span>' . esc_html(get_post_meta(get_the_ID(), 'job_type', true)) . '</span>
                    ';
                $output .= '</div>';
                $output .= '<div class="cta">';
                $output .= '<a class="nectar-button large regular accent-color  regular-button" role="button" href="' . get_permalink() . '"> Apply</a>';
                $output .= '</div>';
                $output .= '</div>';
                }
            $output .= '</div>';

            // Reset post data
            wp_reset_postdata();
            } else {
            $output = '<p>No opportunities available</p>';
            }

        return $output;
        }

    // Shortcode to display jobs
    public function display_reconcile_jobs()
        {
        if (is_a($GLOBALS['post'], 'WP_Post') && stripos($GLOBALS['post']->post_content, '[reconcile_jobs') !== false) {
            wp_enqueue_style('reconcile-jobs-css', plugin_dir_url(__FILE__) . 'styles/style.css', false);
            wp_enqueue_script('reconcile-jobs-ajax-script', plugin_dir_url(__FILE__) . 'js/reconcile-jobs-ajax.js', ['jquery'], null, true);
            wp_localize_script('reconcile-jobs-ajax-script', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
            }

        // Define the post type
        $post_type = 'job';

        // Get current date in mm/dd/yyyy format
        $current_date = date('m/d/Y');

        // Convert current date to a format suitable for comparison (Y-m-d)
        $current_date_sql = date('Y-m-d', strtotime($current_date));

        // Query for all jobs with a close_date greater than the current date
        $query = new WP_Query([
            'post_type' => $post_type,
            'post_status' => ['publish'],
            'posts_per_page' => -1, // Retrieve all posts
            'meta_query' => [
                [
                    'key' => 'close_date',
                    'value' => $current_date_sql,
                    'compare' => '>',
                    'type' => 'DATE', // Ensure proper date comparison
                ],
            ],
        ]);

        // Check if there are posts
        if ($query->have_posts()) {
            $output = '<h2> Current Openings</h2>';
            $output .= '<div class="jobs">';

            // Loop through posts and display them
            while ($query->have_posts()) {
                $query->the_post();
                $output .= '<div class="single-job">';
                $output .= '<div class="details">
                <a href="' . get_permalink() . '">' . get_the_title() . '</a>
                <span>' . esc_html(get_post_meta(get_the_ID(), 'location', true)) . '</span>
               <span>Deadline: ' . esc_html(get_post_meta(get_the_ID(), 'close_date', true)) . '</span>
                ';
                $output .= '</div>';
                $output .= '<div class="cta">';
                $output .= '<a class="nectar-button large regular accent-color  regular-button" role="button" href="' . get_permalink() . '"> Apply</a>';
                $output .= '</div>';
                $output .= '</div>';
                }
            $output .= '</div>';

            // Reset post data
            wp_reset_postdata();
            } else {
            $output = '<p>No jobs available</p>';
            }

        return $output;
        }

    public function custom_job_template_include($template)
        {
        if (is_singular('job')) {
            wp_enqueue_style('single-job-style', plugins_url('styles/single-job-styles.css', __FILE__));
            // Path to the custom template file
            $custom_template = plugin_dir_path(__FILE__) . '/includes/templates/single-job.php';

            if (file_exists($custom_template)) {
                return $custom_template;
                }
            }

        return $template;
        }

    // Shortcode to display menu categories and container
    public function display_reconcile_tenders()
        {
        if (is_a($GLOBALS['post'], 'WP_Post') && stripos($GLOBALS['post']->post_content, '[reconcile_jobs') !== false) {
            wp_enqueue_style('reconcile-jobs-css', plugin_dir_url(__FILE__) . 'styles/style.css', false);
            wp_enqueue_script('reconcile-jobs-ajax-script', plugin_dir_url(__FILE__) . 'js/reconcile-jobs-ajax.js', ['jquery'], null, true);
            wp_localize_script('reconcile-jobs-ajax-script', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
            }

        // Define the post type
        $post_type = 'tender';

        // Get current date in mm/dd/yyyy format
        $current_date = date('m/d/Y');

        // Convert current date to a format suitable for comparison (Y-m-d)
        $current_date_sql = date('Y-m-d', strtotime($current_date));

        // Query for all jobs with a close_date greater than the current date
        $query = new WP_Query([
            'post_type' => $post_type,
            'posts_per_page' => -1, // Retrieve all posts
            'post_status' => ['publish'],
            'meta_query' => [
                [
                    'key' => 'close_date',
                    'value' => $current_date_sql,
                    'compare' => '>',
                    'type' => 'DATE', // Ensure proper date comparison
                ],
            ],
        ]);

        // Check if there are posts
        if ($query->have_posts()) {
            $output = '<h2> Current Tender Opportunities</h2>';
            $output .= '<div class="tenders">';

            // Loop through posts and display them
            while ($query->have_posts()) {
                $query->the_post();
                $output .= '<div class="single-tender">';
                $output .= '<div class="details">
                    <a href="' . get_permalink() . '">' . get_the_title() . '</a>
                   <span>Ref No: ' . esc_html(get_post_meta(get_the_ID(), 'reference_number', true)) . '</span>
                   <span>Deadline ' . esc_html(get_post_meta(get_the_ID(), 'close_date', true)) . '</span>
                    ';
                $output .= '</div>';
                $output .= '<div class="cta">';
                $output .= '<a class="nectar-button large regular accent-color  regular-button" role="button" href="' . esc_html(wp_get_attachment_url(get_post_meta(get_the_ID(), 'download', true))) . '"> Download </a>';
                $output .= '</div>';
                $output .= '</div>';
                }
            $output .= '</div>';

            // Reset post data
            wp_reset_postdata();
            } else {
            $output = '<p>No procurement notice at the moment</p>';
            }

        return $output;
        }

    public function custom_tender_template_include($template)
        {
        if (is_singular('tender')) {
            wp_enqueue_style('single-tender-style', plugins_url('styles/single-tender-styles.css', __FILE__));
            // Path to the custom template file
            $custom_template = plugin_dir_path(__FILE__) . '/includes/templates/single-tender.php';

            if (file_exists($custom_template)) {
                return $custom_template;
                }
            }

        return $template;
        }
    }

// Instantiate our class
$Reconcile_EA = Reconcile_EA::getInstance();
