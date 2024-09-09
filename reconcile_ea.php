<?php
/*
    Plugin Name: Reconcile EA
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

define('RECONCILE_EA_VERSION', '1.0.0');

class Reconcile_EA
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
        add_shortcode('reconcile_opportunities', [$this, 'display_reconcile_opportunities']);
        add_shortcode('reconcile_jobs', [$this, 'display_reconcile_jobs']);
        add_shortcode('reconcile_procurement', [$this, 'display_reconcile_tenders']);
        add_shortcode('reconcile_projects', [$this, 'display_reconcile_projects']);
        }

    public function enqueue_assets()
        {
        if (
            is_singular(['job', 'tender']) || has_shortcode(get_post()->post_content, 'reconcile_opportunities') ||
            has_shortcode(get_post()->post_content, 'reconcile_jobs') ||
            has_shortcode(get_post()->post_content, 'reconcile_procurement')
        ) {
            wp_enqueue_style('reconcile-styles', plugin_dir_url(__FILE__) . 'styles/style.css', [], RECONCILE_EA_VERSION);
            wp_enqueue_script('reconcile-ajax-script', plugin_dir_url(__FILE__) . 'js/reconcile.js', ['jquery'], RECONCILE_EA_VERSION, true);
            wp_localize_script('reconcile-ajax-script', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
            }

        if (has_shortcode(get_post()->post_content, 'reconcile_projects')) {
            wp_enqueue_style('reconcile-styles', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', []);
            wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.3/dist/leaflet.js', ['jquery']);
            wp_enqueue_script('reconcile-projects-script', plugin_dir_url(__FILE__) . 'js/map.js', ['jquery'], RECONCILE_EA_VERSION, true);
            }
        }

    public function custom_template_include($template)
        {
        $post_type = get_post_type();
        if (is_singular(['job', 'tender'])) {
            $custom_template = plugin_dir_path(__FILE__) . "includes/templates/single-{$post_type}.php";
            if (file_exists($custom_template)) {
                return $custom_template;
                }
            }
        return $template;
        }

    private function get_opportunities($post_type)
        {
        $current_date = date('Y-m-d');
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

    public function display_reconcile_opportunities()
        {
        return $this->display_items(['job', 'tender'], 'Current Opportunities');
        }

    public function display_reconcile_jobs()
        {
        return $this->display_items('job', 'Current Openings');
        }

    public function display_reconcile_tenders()
        {
        return $this->display_items('tender', 'Current Tender Opportunities');
        }

    public function display_reconcile_projects()
        {
        ob_start();
        ?>
        <div id="projects-map" style="height: 600px; width: 100%;"></div>
        <?php
        // Return the buffered content
        return ob_get_clean();
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

Reconcile_EA::getInstance();