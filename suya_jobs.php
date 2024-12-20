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

class Suya_jobs
    {
    private static $instance = null;

    private function __construct()
        {
        add_action('init', [$this, 'register_shortcodes']);
        add_filter('template_include', [$this, 'custom_template_include']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        }

    public static function getInstance()
        {
        if (self::$instance === null) {
            self::$instance = new self();
            }
        return self::$instance;
        }

    public function register_shortcodes()
        {
        add_shortcode('suya_opportunities', [$this, 'display_suya_opportunities']);
        add_shortcode('suya_jobs', [$this, 'display_suya_jobs']);
        add_shortcode('suya_procurement', [$this, 'display_suya_tenders']);
        add_shortcode('suya_projects', [$this, 'display_suya_projects']);
        add_shortcode('suya_events', [$this, 'display_suya_events']);
        }

    public function enqueue_assets()
        {
        if (
            is_singular(['job', 'tender', 'event']) || has_shortcode(get_post()->post_content, 'suya_opportunities') ||
            has_shortcode(get_post()->post_content, 'suya_jobs') ||
            has_shortcode(get_post()->post_content, 'suya_procurement') ||
            has_shortcode(get_post()->post_content, 'suya_events')
        ) {
            wp_enqueue_style('suya-styles', plugin_dir_url(__FILE__) . 'styles/style.css', [], SUYA_JOBS_VERSION);
            wp_enqueue_script('suya-ajax-script', plugin_dir_url(__FILE__) . 'js/suya.js', ['jquery'], SUYA_JOBS_VERSION, true);
            wp_localize_script('suya-ajax-script', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
            }

        if (has_shortcode(get_post()->post_content, 'suya_projects')) {
            wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', []);
            wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.3/dist/leaflet.js', ['jquery']);
            wp_enqueue_script('suya-projects-script', plugin_dir_url(__FILE__) . 'js/map.js', ['jquery'], SUYA_JOBS_VERSION, true);
            }
        }

    public function custom_template_include($template)
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

    private function get_opportunities($post_type)
        {
        $current_date = gmdate('Y-m-d');
        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'close_date',
                    'value' => $current_date,
                    'compare' => '>',
                    'type' => 'DATE',
                ],
            ],
        ];
        return new WP_Query($args);
        }

    public function display_suya_opportunities()
        {
        return $this->display_items(['job'], 'Current Opportunities');
        }

    public function display_suya_jobs()
        {
        return $this->display_items('job', 'Current Openings');
        }




    private function display_items($post_type, $title)
        {
        $query = $this->get_opportunities($post_type);
        if (!$query->have_posts()) {
            return '<p>No ' . strtolower($title) . ' available</p>';
            }

        $output = "<h2>{$title}</h2><div class='" . (is_array($post_type) ? 'jobs' : $post_type . 's') . "'>";
        while ($query->have_posts()) {
            $query->the_post();
            $output .= $this->get_item_html(get_post_type());
            }
        wp_reset_postdata();
        return $output . '</div>';
        }

    private function get_item_html($post_type)
        {
        $html = '<div class="single-' . $post_type . '"><div class="details">';
        $html .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';

        if ($post_type === 'job') {
            $html .= '<span>' . esc_html(get_post_meta(get_the_ID(), 'location', true)) . '</span>';
            } elseif ($post_type === 'tender') {
            $html .= '<span>Ref No: ' . esc_html(get_post_meta(get_the_ID(), 'reference_number', true)) . '</span>';
            }

        $html .= '<span>' . ($post_type === 'job' ? 'Deadline: ' : 'Deadline ') . esc_html(get_post_meta(get_the_ID(), 'close_date', true)) . '</span>';

        $html .= '</div><div class="cta">';
        if ($post_type === 'job') {
            $html .= '<a class="nectar-button large regular accent-color regular-button" href="' . get_permalink() . '">Apply</a>';
            } elseif ($post_type === 'tender') {
            $html .= '<a class="nectar-button large regular accent-color regular-button" href="' . esc_url(wp_get_attachment_url(get_post_meta(get_the_ID(), 'download', true))) . '">Download</a>';
            }
        $html .= '</div></div>';
        return $html;
        }
    }

Suya_jobs::getInstance();