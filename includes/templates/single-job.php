<?php
/**
 * Template Name: Single Job Template
 * 
 * This is the template that displays a single job post.
 */

get_header();
?>
<div class="job-single-container">
    <?php
    // Add hook for Elementor
    do_action('before_job_content');

    ?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <?php
            while (have_posts()) {
                the_post();


                // Allow Elementor or Block Editor to handle the title if they want
                if (!defined('ELEMENTOR_VERSION') || !elementor_theme_do_location('single')) {
                    echo '<header class="entry-header">';
                    the_title('<h1 class="entry-title">', '</h1>');
                    echo '</header>';
                    }

                ?>
                <!-- Job Details -->
                <div class="job-content">
                    <!-- Job Metadata -->
                    <?php
                    // Job details
                    echo '<div class="job-meta">';

                    // Location
                    $location = get_post_meta(get_the_ID(), '_location', true);
                    if ($location) {
                        echo '<div class="job-location">';
                        echo '<strong>' . esc_html__('Location:', 'suya-jobs') . '</strong> ';
                        echo esc_html($location);
                        echo '</div>';
                        }

                    // Job Type
                    $job_type = get_post_meta(get_the_ID(), '_job_type', true);
                    if ($job_type) {
                        echo '<div class="job-type">';
                        echo '<strong>' . esc_html__('Job Type:', 'suya-jobs') . '</strong> ';
                        echo esc_html(ucfirst(str_replace('_', ' ', $job_type)));
                        echo '</div>';
                        }

                    // Close Date
                    $close_date = get_post_meta(get_the_ID(), '_close_date', true);
                    if ($close_date) {
                        echo '<div class="job-close-date">';
                        echo '<strong>' . esc_html__('Application Deadline:', 'suya-jobs') . '</strong> ';
                        echo esc_html(date_i18n(get_option('date_format'), strtotime($close_date)));
                        echo '</div>';
                        }

                    echo '</div>'; // .job-meta
                    ?>
                    <!-- Add any other job metadata here -->

                    <?php // Content
                        echo '<div class="entry-content">';

                        // Check if using Elementor
                        if (defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->documents->get(get_the_ID())->is_built_with_elementor()) {
                            echo \Elementor\Plugin::$instance->frontend->get_builder_content(get_the_ID(), true);
                            } else {
                            // Regular content (Block Editor or Classic)
                            the_content();
                            }

                        echo '</div>'; // .entry-content
                    
                        // Download attachment if exists
                        $download_id = get_post_meta(get_the_ID(), '_download', true);
                        if ($download_id) {
                            $download_url = wp_get_attachment_url($download_id);
                            if ($download_url) {
                                echo '<div class="job-download">';
                                echo '<a href="' . esc_url($download_url) . '" class="button download-button">';
                                echo esc_html__('Download Job Description', 'suya-jobs');
                                echo '</a>';
                                echo '</div>';
                                }
                            }
                        ?>


                </div>

                <!-- FormCraft Form -->
                <div class="formcraft-form">
                    <?php
                    if (function_exists('add_formcraft_form')) {
                        add_formcraft_form("[fc id='1'][/fc]");
                        }
                    ?>


                    <script type="text/javascript">
                        document.addEventListener('DOMContentLoaded', function () {
                            // Autofill the job position in the form
                            var jobPositionField = document.querySelector('input[name="field4[]"]'); // Change this selector to match your form field
                            if (jobPositionField) {
                                jobPositionField.value = "<?php echo esc_js(get_the_title()); ?>";
                            }
                            console.log('test')
                        });

                    </script>

                </div>
                <?php
                }

            ?>
        </main>
    </div>
</div>
<?php
// Add some basic styles for the job template
wp_add_inline_style('suya-jobs', '
    .job-single {
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
?>
<?php
get_footer(); // Include the footer
?>