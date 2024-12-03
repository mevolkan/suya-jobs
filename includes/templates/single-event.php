<?php
/*
 * Template Name: Single Event
 * Template Post Type: event
 */
get_header();
if (have_posts()) {
    while (have_posts()) {
        the_post();
        ?>
        <div class="container main-content">
            <div class="event-single">
                <h1><?php the_title(); ?></h1>

                <!-- Event Details -->
                <div class="event-content">
                    <div>
                        <?php the_post_thumbnail('thumbnail'); ?>
                    </div>

                    <!-- Event Metadata -->
                    <div class="event-meta">
                        <?php
                        // Function to check and display metadata
                        function display_event_meta($label, $meta_key)
                            {
                            $value = get_post_meta(get_the_ID(), $meta_key, true);
                            if (!empty(trim($value))) {
                                ?>
                                <div>
                                    <strong><?php echo esc_html($label); ?>:</strong>
                                    <?php echo esc_html($value); ?>
                                </div>
                                <?php
                                }
                            }

                        // Display metadata fields conditionally
                        display_event_meta('Organizer', 'organizer');
                        display_event_meta('Location', 'location');
                        display_event_meta('Website', 'event_website');
                        display_event_meta('Online Link', 'event_online_link');
                        display_event_meta('Registration Link', 'event_registration_link');
                        ?>
                        <div class="event-time">
                            <?php
                            // Function to format and display event time metadata
                            function display_event_time_meta()
                                {
                                $event_date = get_post_meta(get_the_ID(), 'event_date', true);
                                $event_time = get_post_meta(get_the_ID(), 'event_time', true);
                                $event_end_time = get_post_meta(get_the_ID(), 'event_end_time', true);
                                $all_day_event = get_post_meta(get_the_ID(), 'all_day_event', true);

                                // Convert date to a more readable format
                                if (!empty(trim($event_date))) {
                                    try {
                                        $formatted_date = date_create_from_format('Y-m-d', $event_date);
                                        if ($formatted_date) {
                                            $readable_date = date_format($formatted_date, 'l, F j, Y');
                                            } else {
                                            $readable_date = $event_date;
                                            }
                                        } catch (Exception $e) {
                                        $readable_date = $event_date;
                                        }
                                    ?>
                                    <div class="event-date">
                                        <strong>Date:</strong> <?php echo esc_html($readable_date); ?>
                                    </div>
                                    <?php
                                    }

                                // Handle all-day event
                                if (!empty(trim($all_day_event)) && filter_var($all_day_event, FILTER_VALIDATE_BOOLEAN)) {
                                    ?>
                                    <div class="all-day-event">
                                        <strong>Event Type:</strong> All-Day Event
                                    </div>
                                    <?php
                                    } else {
                                    // Display start and end times if not an all-day event
                                    if (!empty(trim($event_time))) {
                                        ?>
                                        <div class="event-start-time">
                                            <strong>Start Time:</strong> <?php echo esc_html($event_time); ?>
                                        </div>
                                        <?php
                                        }

                                    if (!empty(trim($event_end_time))) {
                                        ?>
                                        <div class="event-end-time">
                                            <strong>End Time:</strong> <?php echo esc_html($event_end_time); ?>
                                        </div>
                                        <?php
                                        }
                                    }
                                }

                            // Call the function to display event time metadata
                            display_event_time_meta();
                            ?>
                        </div>
                    </div>

                    <!-- Event Content -->
                    <?php
                    $content = get_the_content();
                    if (!empty(trim($content))) {
                        ?>
                        <div class="event-description">
                            <?php the_content(); ?>
                        </div>
                        <?php
                        }
                    ?>
                </div>
            </div>
        </div>
        <?php
        }
    }
get_footer();
?>