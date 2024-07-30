<?php
get_header(); // Include the header

if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        ?>
        <div class="container main-content">
            <div class="single-job">
                <h1><?php the_title(); ?></h1>

                <!-- Job Details -->
                <div class="job-content">
                    <!-- Job Metadata -->
                    <div class="details">
                        <div>
                            <p><strong>Location:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'location', true ) ); ?></p>
                        </div>
                        <div>
                            <p><strong>Close Date:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'close_date', true ) ); ?></p>
                        </div>
                        <div>
                           <a class="nectar-button large regular accent-color  regular-button" role="button" href="<?php echo esc_html( wp_get_attachment_url( get_post_meta( get_the_ID(), 'download', true ) ) ); ?>"> Download </a></p>
                        </div>
                    </div>
                    <!-- Add any other job metadata here -->

                    <?php the_content(); // Display the job description
        ?>


                </div>

                <!-- FormCraft Form -->
                <div class="formcraft-form">
                <?php
                              if ( function_exists( 'add_formcraft_form' ) ) {
                                  add_formcraft_form( "[fc id='1'][/fc]" );
                              }
        ?>
						

                    <script type="text/javascript">
                        document.addEventListener('DOMContentLoaded', function() {
                            // Autofill the job position in the form
                            var jobPositionField = document.querySelector('input[name="field4[]"]'); // Change this selector to match your form field
                            if (jobPositionField) {
                                jobPositionField.value = "<?php echo esc_js( get_the_title() ); ?>";
                            }
                            console.log('test')
                        });
                        
                    </script>
                </div>
            </div>
        </div>
<?php
    }
}

get_footer(); // Include the footer
?>