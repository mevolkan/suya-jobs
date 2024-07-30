<?php
get_header(); // Include the header

if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        ?>
        <div class="container main-content">
            <div class="tender-single">
                <h1><?php the_title(); ?></h1>

                <!-- tender Details -->
                <div class="tender-content">
                    <!-- tender Metadata -->
                    <div class="tender-meta">
                        <div>
                            <strong>Reference Number:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'reference_number', true ) ); ?>
                        </div>
                        <div>
                            <strong>Close Date:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'close_date', true ) ); ?>
                        </div>
                        <div>
                            <a class="nectar-button large regular accent-color  regular-button" role="button" href="<?php echo esc_html( wp_get_attachment_url( get_post_meta( get_the_ID(), 'download', true ) ) ); ?>"> Download Tender Document</a></p>
                        </div>
                    </div>
                    <!-- Add any other tender metadata here -->

                    <?php the_content(); // Display the tender description
        ?>
                </div>
            </div>
        </div>
        </div>
<?php
    }
}

get_footer(); // Include the footer
?>