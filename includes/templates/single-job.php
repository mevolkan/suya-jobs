<?php
/**
 * Template Name: Single Job Template
 * 
 * This template displays a single job post with its details, metadata,
 * and application form integration.
 */

get_header();

// Add hook for Elementor
do_action('before_job_content');
?>

<div class="job-single-container">
    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <?php while (have_posts()) : the_post(); ?>
                
                <?php if (!defined('ELEMENTOR_VERSION') || !elementor_theme_do_location('single')) : ?>
                    <header class="entry-header">
                        <h1 class="entry-title"><?php the_title(); ?></h1>
                    </header>
                <?php endif; ?>

                <div class="job-content">
                    <!-- Job Metadata -->
                    <div class="job-meta">
                        <?php
                        $meta_fields = [
                            'location' => [
                                'key' => '_location',
                                'label' => __('Location:', 'suya-jobs')
                            ],
                            'job_type' => [
                                'key' => '_job_type',
                                'label' => __('Job Type:', 'suya-jobs'),
                                'transform' => function($value) {
                                    return ucfirst(str_replace('_', ' ', $value));
                                }
                            ],
                            'close_date' => [
                                'key' => '_close_date',
                                'label' => __('Application Deadline:', 'suya-jobs'),
                                'transform' => function($value) {
                                    return date_i18n(get_option('date_format'), strtotime($value));
                                }
                            ]
                        ];

                        foreach ($meta_fields as $field) {
                            $value = get_post_meta(get_the_ID(), $field['key'], true);
                            if ($value) {
                                echo '<div class="job-' . sanitize_html_class($field['key']) . '">';
                                echo '<strong>' . esc_html($field['label']) . '</strong> ';
                                echo isset($field['transform']) 
                                    ? esc_html($field['transform']($value))
                                    : esc_html($value);
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>

                    <!-- Job Content -->
                    <div class="entry-content">
                        <?php
                        if (defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->documents->get(get_the_ID())->is_built_with_elementor()) {
                            echo \Elementor\Plugin::$instance->frontend->get_builder_content(get_the_ID(), true);
                        } else {
                            the_content();
                        }
                        ?>
                    </div>

                    <!-- Job Description Download -->
                    <?php
                    $download_id = get_post_meta(get_the_ID(), '_download', true);
                    if ($download_id && $download_url = wp_get_attachment_url($download_id)) : ?>
                        <div class="job-download">
                            <a href="<?php echo esc_url($download_url); ?>" class="button download-button">
                                <?php esc_html_e('Download Job Description', 'suya-jobs'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Application Form -->
                <?php if (function_exists('add_formcraft_form')) : ?>
                    <div class="formcraft-form">
                        <?php add_formcraft_form("[fc id='1'][/fc]"); ?>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const jobPositionField = document.querySelector('input[name="field11[]"]');
                                if (jobPositionField) {
                                    jobPositionField.value = <?php echo wp_json_encode(get_the_title()); ?>;
                                }
                            });
                        </script>
                    </div>
                <?php endif; ?>

            <?php endwhile; ?>
        </main>
    </div>
</div>

<?php
// Add inline styles
wp_add_inline_style('suya-jobs', '
    .job-single-container {
        max-width: 1200px;
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
');

// Handle footer based on theme type
if (function_exists('elementor_theme_do_location') && elementor_theme_do_location('footer')) {
    // Elementor footer is handled
} elseif (function_exists('wp_block_template_part')) {
    wp_block_template_part('footer');
} else {
    get_footer();
}