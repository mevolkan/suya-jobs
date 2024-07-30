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

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !defined( 'Reconcile_EA' ) ) {
    define( 'Reconcile_EA', '1.0.0' );
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
        // Hook into WordPress AJAX action
        add_action( 'wp_ajax_fetch_menu_items', [$this, 'fetch_menu_items'] );
        add_action( 'wp_ajax_nopriv_fetch_menu_items', [$this, 'fetch_menu_items'] );

        add_shortcode( 'reconcile_jobs', [$this, 'display_reconcile_jobs' ] );

        add_action( 'wp_ajax_fetch_pantry_items', [$this, 'fetch_pantry_items'] );
        add_action( 'wp_ajax_nopriv_fetch_pantry_items', [$this, 'fetch_pantry_items'] );

        add_shortcode( 'java_pantry', [$this, 'display_java_pantry'] );
    }

    /**
     * If an instance exists, this returns it.  If not, it creates one and
     * retuns it.
     *
     * @return Reconcile_EA
     */
    public static function getInstance()
    {
        if ( !self::$instance ) {
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
        load_plugin_textdomain( 'reconcile-ea', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    // AJAX handler to fetch menu items
    public function fetch_menu_items()
    {
        // Check if category_id is provided and is numeric
        if ( !isset( $_POST['category_id'] ) || !is_numeric( $_POST['category_id'] ) ) {
            wp_send_json_error( 'Invalid category ID' );
        }

        $category_id = stripslashes( intval( $_POST['category_id'] ) );
        $paged       = stripslashes( isset( $_POST['paged'] ) ) ? stripslashes( intval( $_POST['paged'] ) ) : 1;

        // Fetch parent category if category_id is not 0
        $parent_category = null;

        if ( $category_id !== 0 ) {
            $parent_category = get_term( $category_id, 'menu_category' );

            if ( !$parent_category || is_wp_error( $parent_category ) ) {
                wp_send_json_error( 'Parent category not found' );
            }
        }

        // Fetch child categories based on category_id, including subcategories
        $args = [
            'taxonomy'   => 'menu_category',
            'hide_empty' => false,
        ];

        if ( $category_id !== 0 ) {
            $args['parent'] = $category_id;
        }

        $child_categories = get_terms( $args );

        $response = [];

        // Loop through child categories
        foreach ( $child_categories as $child_category ) {
            $args = [
                'post_type'      => 'menu_item',
                'posts_per_page' => 20,
                'paged'          => $paged,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'tax_query'      => [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'menu_category',
                        'field'    => 'term_id',
                        'terms'    => $child_category->term_id,
                    ],
                ],
            ];

            if ( $category_id !== 0 ) {
                $args['tax_query'][] = [
                    'taxonomy' => 'menu_category',
                    'field'    => 'term_id',
                    'terms'    => $parent_category->term_id,
                ];
            }

            $query = new WP_Query( $args );

            if ( $query->have_posts() ) {
                $category_items = [];

                while ( $query->have_posts() ) {
                    $query->the_post();

                    // Fetch allergen images (assuming allergen taxonomy is used)
                    $allergen_images = [];
                    $allergen_terms  = get_the_terms( get_the_ID(), 'allergen' );

                    if ( $allergen_terms && !is_wp_error( $allergen_terms ) ) {
                        foreach ( $allergen_terms as $allergen_term ) {
                            $allergen_image_id = get_term_meta( $allergen_term->term_id, 'allergen_image', true );

                            if ( $allergen_image_id ) {
                                $allergen_images[] = wp_get_attachment_url( $allergen_image_id );
                            }
                        }
                    }

                    // Collect menu item details
                    $category_items[] = [
                        'title'             => get_the_title(),
                        'allergen_advisory' => $allergen_images,
                        'normal_price'      => get_post_meta( get_the_ID(), 'normal_price', true ),
                        'medium_price'      => get_post_meta( get_the_ID(), 'medium_price', true ),
                        'large_price'       => get_post_meta( get_the_ID(), 'large_price', true ),
                        'half_serve'        => get_post_meta( get_the_ID(), 'half_serve', true ),
                        'full_serve'        => get_post_meta( get_the_ID(), 'full_serve', true ),
                        'description'       => get_post_meta( get_the_ID(), 'description', true ),
                        'single'            => get_post_meta( get_the_ID(), 'single', true ),
                        'double'            => get_post_meta( get_the_ID(), 'double', true ),
                        'triple'            => get_post_meta( get_the_ID(), 'triple', true ),
                        'double_dish'       => get_post_meta( get_the_ID(), 'double_dish', true ),
                        'five_pieces'       => get_post_meta( get_the_ID(), 'five_pieces', true ),
                        'ten_pieces'        => get_post_meta( get_the_ID(), 'ten_pieces', true ),
                        'triple_dish'       => get_post_meta( get_the_ID(), 'triple_dish', true ),
                    ];
                }

                $response[$child_category->term_id] = [
                    'category_name'        => $child_category->name,
                    'category_description' => !empty( $child_category->description ) ? $child_category->description : '',
                    'category_thumbnail'   => wp_get_attachment_url( get_term_meta( $child_category->term_id, 'featured_image', true ) ),
                    'items'                => $category_items,
                ];

                wp_reset_postdata();
            }
        }

        // Send JSON response
        if ( !empty( $response ) ) {
            wp_send_json_success( $response );
        } else {
            wp_send_json_error( 'No menu items found' );
        }
    }

    // Shortcode to display menu categories and container
    public function display_reconcile_jobs()
    {
        if ( is_a( $GLOBALS['post'], 'WP_Post' ) && stripos( $GLOBALS['post']->post_content, '[java_menu]' ) !== false ) {
            wp_enqueue_style( 'reconcile-jobs-css', plugin_dir_url( __FILE__ ) . 'styles/style.css', false );
            wp_enqueue_script( 'reconcile-jobs-ajax-script', plugin_dir_url( __FILE__ ) . 'js/reconcile-jobs-ajax.js', ['jquery'], null, true );
            wp_localize_script( 'reconcile-jobs-ajax-script', 'ajax_object', ['ajax_url' => admin_url( 'admin-ajax.php' )] );
        }

        // Define the post type
        $post_type = 'job';

        // Get current date in mm/dd/yyyy format
        $current_date = date( 'm/d/Y' );

        // Convert current date to a format suitable for comparison (Y-m-d)
        $current_date_sql = date( 'Y-m-d', strtotime( $current_date ) );

        // Query for all jobs with a close_date greater than the current date
        $query = new WP_Query( [
            'post_type'      => $post_type,
            'posts_per_page' => -1, // Retrieve all posts
            'meta_query'     => [
                [
                    'key'     => 'close_date',
                    'value'   => $current_date_sql,
                    'compare' => '>',
                    'type'    => 'DATE', // Ensure proper date comparison
                ],
            ],
        ] );

        // Check if there are posts
        if ( $query->have_posts() ) {
            echo '<h2> Current Openings</h2>';
            echo '<ul>';

            // Loop through posts and display them
            while ( $query->have_posts() ) {
                $query->the_post();
                echo '<li>';
                echo '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
                echo ' Close Date: ' . esc_html( get_post_meta( get_the_ID(), 'close_date', true ) ) . '';
                echo '<a class="nectar-button large regular accent-color  regular-button" role="button" href="' . get_permalink() . '"> Apply</a>';
                echo '</li>';
            }
            echo '</ul>';

            // Reset post data
            wp_reset_postdata();
        } else {
            echo '<p>No jobs available</p>';
        }
    }

    // AJAX handler to fetch Pantry items
    public function fetch_pantry_items()
    {
        if ( !isset( $_POST['category_id'] ) || !is_numeric( $_POST['category_id'] ) ) {
            wp_send_json_error( 'Invalid category ID' );
        }

        $category_id = stripslashes( intval( $_POST['category_id'] ) );
        $paged       = stripslashes( isset( $_POST['paged'] ) ) ? stripslashes( intval( $_POST['paged'] ) ) : 1;

        $args = [
            'post_type'      => 'pantry_item',
            'posts_per_page' => 20,
            'paged'          => $paged,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ];

        // Fetch parent category if category_id is not 0
        if ( $category_id !== 0 ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'pantry_category',
                    'field'    => 'term_id',
                    'terms'    => $category_id,
                ],
            ];
        }

        $response = [];

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();

                // Get region terms associated with the current pantry item
                $region_terms = get_the_terms( get_the_ID(), 'region' );

                // Initialize variables to store taxonomy name and featured image URL
                $region_name     = '';
                $region_flag_url = '';

                // Check if region terms exist and are not a WP_Error
                // Check if region terms exist and are not a WP_Error
                if ( $region_terms && !is_wp_error( $region_terms ) ) {
                    // Assuming you want to work with the first region term
                    $region_term = array_shift( $region_terms );

                    // Get the region name
                    $region_name = $region_term->name;

                    // Get the featured image associated with the region term (stored as term meta)
                    $region_flag_url = '';

                    // Get the featured image ID from term meta
                    $region_flag_id = get_term_meta( $region_term->term_id, 'flag', true );

                    // Check if the featured image ID is not empty
                    if ( !empty( $region_flag_id ) ) {
                        // Get the URL of the featured image using the ID
                        $region_flag_url = wp_get_attachment_url( $region_flag_id );
                    }
                }

                // Build the response array for each pantry item
                $response[] = [
                    'title'             => get_the_title(),
                    'content'           => get_the_content(),
                    'featured_image'    => get_the_post_thumbnail_url( get_the_ID(), 'full' ),
                    'nutritional_facts' => wp_get_attachment_image_url( get_post_meta( get_the_ID(), 'nutrition_facts', true ), '' ),
                    'recipes'           => wp_get_attachment_url( get_post_meta( get_the_ID(), 'recipes', true ), '' ),
                    'learn'             => get_post_meta( get_the_ID(), 'learn', true ),
                    'available'         => get_post_meta( get_the_ID(), 'available', false ),
                    'gourmet'           => get_post_meta( get_the_ID(), 'gourmet', true ),
                    'intensity'         => get_post_meta( get_the_ID(), 'intensity', true ),
                    'region'            => $region_name,
                    'region_flag'       => $region_flag_url,
                    'tasting_notes'     => get_post_meta( get_the_ID(), 'tasting_notes', true ),
                    'grind_size'        => get_post_meta( get_the_ID(), 'grind_size', false ),
                    'description'       => get_post_meta( get_the_ID(), 'description', true ),
                ];
            }
            wp_reset_postdata();
            wp_send_json_success( $response );
        } else {
            wp_send_json_error( 'No pantry items found' );
        }
    }

    // Shortcode to display Pantry categories and container
    public function display_java_pantry()
    {
        if ( is_a( $GLOBALS['post'], 'WP_Post' ) && stripos( $GLOBALS['post']->post_content, '[java_pantry]' ) !== false ) {
            wp_enqueue_style( 'menu-css', plugin_dir_url( __FILE__ ) . 'styles/style.css', false );
            wp_enqueue_script( 'pantry-ajax-script', plugin_dir_url( __FILE__ ) . 'js/pantry-ajax.js', ['jquery'], null, true );
            wp_localize_script( 'pantry-ajax-script', 'ajax_object', ['ajax_url' => admin_url( 'admin-ajax.php' )] );
        }

        // Fetch parent pantry categories
        $categories = get_terms( [
            'taxonomy'   => 'pantry_category',
            'hide_empty' => false,
            'parent'     => 0, // Only fetch parent categories
        ] );

        if ( !empty( $categories ) && !is_wp_error( $categories ) ) {
            $output = '<div id="pantry-categories">';

            foreach ( $categories as $category ) {
                $category_id   = $category->term_id;
                $category_name = $category->name;

                $output .= '<div class="pantry-category" data-category-id="' . esc_attr( $category_id ) . '">';

                $output .= '<span class="nectar-button large regular regular-button" role="button">' . esc_html( $category_name ) . '</span>';
                $output .= '</div>';
            }
            $output .= '</div>';

            $output .= '<div id="pantry-container">';
            $output .= '<!-- Pantry items will be loaded here -->';
            $output .= '</div>';

            $output .= '<div id="loading-indicator" style="display: none;">Loading...</div>'; // Loading indicator
        } else {
            $output = '<p>No categories found.</p>';
        }

        return $output;
    }
}

// Instantiate our class
$Reconcile_EA = Reconcile_EA::getInstance();
