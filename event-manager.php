<?php
/**
 * Plugin Name: Event Manager
 * Description: Custom Event Management Plugin
 * Version: 1.0
 * Author: Bharat Kumar
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Custom Post Type: Event
 */
function em_register_event_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Events',
        'menu_icon' => 'dashicons-calendar',
        'supports' => array('title', 'editor', 'thumbnail'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'events'),
		'show_in_rest' => true,
    );

    register_post_type('event', $args);
}
add_action('init', 'em_register_event_post_type');

/**
 * Register Custom Taxonomy: Event Categories
 */
function em_register_event_taxonomy() {
    $args = array(
        'label' => 'Event Categories',
        'public' => true,
        'hierarchical' => true,
        'rewrite' => array('slug' => 'event-category'),
    );

    register_taxonomy('event_category', 'event', $args);
}
add_action('init', 'em_register_event_taxonomy');

/**
 * Add Meta Box for Event Details
 */
function em_add_event_meta_box() {
    add_meta_box(
        'em_event_details',
        'Event Details',
        'em_render_event_meta_box',
        'event',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'em_add_event_meta_box');

/**
 * Render Meta Box Fields
 */
function em_render_event_meta_box($post) {
    // Add nonce for security
    wp_nonce_field('em_save_event_meta', 'em_event_meta_nonce');

    $event_date = get_post_meta($post->ID, '_event_date', true);
    $event_location = get_post_meta($post->ID, '_event_location', true);
    ?>

    <p>
        <label for="event_date"><strong>Event Date:</strong></label><br>
        <input type="date"
               id="event_date"
               name="event_date"
               value="<?php echo esc_attr($event_date); ?>" />
    </p>

    <p>
        <label for="event_location"><strong>Event Location:</strong></label><br>
        <input type="text"
               id="event_location"
               name="event_location"
               value="<?php echo esc_attr($event_location); ?>" />
    </p>

    <?php
}

/**
 * Save Meta Box Data Securely
 */
function em_save_event_meta($post_id) {
    // Verify nonce
    if (!isset($_POST['em_event_meta_nonce']) || !wp_verify_nonce($_POST['em_event_meta_nonce'], 'em_save_event_meta')) {
        return;
    }

    // Prevent autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Only for 'event' post type
    if (get_post_type($post_id) !== 'event') {
        return;
    }

    if (isset($_POST['event_date'])) {
        update_post_meta($post_id, '_event_date', sanitize_text_field($_POST['event_date']));
    }

    if (isset($_POST['event_location'])) {
        update_post_meta($post_id, '_event_location', sanitize_text_field($_POST['event_location']));
    }
}
add_action('save_post', 'em_save_event_meta');

/**
 * Add Custom Columns to Event List
 */
function em_add_event_columns($columns) {
    $columns['event_date'] = 'Event Date';
    $columns['event_location'] = 'Location';
    return $columns;
}
add_filter('manage_event_posts_columns', 'em_add_event_columns');

/**
 * Populate Custom Column Data
 */
function em_fill_event_columns($column, $post_id) {
    if ($column === 'event_date') {
        $date = get_post_meta($post_id, '_event_date', true);
        echo esc_html($date);
    }
    if ($column === 'event_location') {
        $location = get_post_meta($post_id, '_event_location', true);
        echo esc_html($location);
    }
}
add_action('manage_event_posts_custom_column', 'em_fill_event_columns', 10, 2);


function em_event_list_shortcode($atts) {

    $atts = shortcode_atts(
        array(
            'posts_per_page' => 5,
            'event_type'     => '',
            'upcoming'       => 'false',
        ),
        $atts,
        'event_list'
    );

    $args = array(
        'post_type'      => 'event',
        'posts_per_page' => intval($atts['posts_per_page']),
        'post_status'    => 'publish',
    );

    // Taxonomy filtering
    if (!empty($atts['event_type'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($atts['event_type']),
            ),
        );
    }

    // Upcoming events filtering
    if ($atts['upcoming'] === 'true') {

        $today = date('Y-m-d');

        $args['meta_query'] = array(
            array(
                'key'     => '_event_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        );

        $args['orderby']  = 'meta_value';
        $args['meta_key'] = '_event_date';
        $args['order']    = 'ASC';
    }

    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()) {

        echo '<div class="em-event-list">';

        while ($query->have_posts()) {
			// print_r(get_post_meta(get_the_ID(), '_event_attendees', true));

            $query->the_post();

            $event_date     = get_post_meta(get_the_ID(), '_event_date', true);
            $event_location = get_post_meta(get_the_ID(), '_event_location', true);

            echo '<div class="em-event-item">';
            echo '<h3><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';

            if ($event_date) {
                echo '<p><strong>Date:</strong> ' . esc_html($event_date) . '</p>';
            }

            if ($event_location) {
                echo '<p><strong>Location:</strong> ' . esc_html($event_location) . '</p>';
            }

            echo '</div>';
        }

        echo '</div>';

    } else {
        echo '<p>No events found.</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}


add_shortcode('event_list', 'em_event_list_shortcode');

function em_send_event_publish_email($new_status, $old_status, $post) {

    // Only for event post type
    if ($post->post_type !== 'event') {
        return;
    }

    // Only when transitioning to publish
    if ($new_status === 'publish' && $old_status !== 'publish') {

        $event_title = $post->post_title;
        $event_link  = get_permalink($post->ID);
        $event_date  = get_post_meta($post->ID, '_event_date', true);
        $event_location = get_post_meta($post->ID, '_event_location', true);

        $admin_email = get_option('admin_email');

        $subject = 'New Event Published: ' . $event_title;

        $message  = "A new event has been published:\n\n";
        $message .= "Title: " . $event_title . "\n";
        $message .= "Date: " . $event_date . "\n";
        $message .= "Location: " . $event_location . "\n\n";
        $message .= "View Event: " . $event_link . "\n";

        wp_mail($admin_email, $subject, $message);
    }
}

add_action('transition_post_status', 'em_send_event_publish_email', 10, 3);

function em_display_rsvp_form($content) {

    if (!is_singular('event') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    ob_start();
    ?>

    <div class="em-rsvp-form">
        <h3>RSVP for this Event</h3>
        <form method="post">
            <?php wp_nonce_field('em_rsvp_action', 'em_rsvp_nonce'); ?>

            <p>
                <label>Your Name</label><br>
                <input type="text" name="em_rsvp_name" required>
            </p>

            <p>
                <label>Your Email</label><br>
                <input type="email" name="em_rsvp_email" required>
            </p>

            <p>
                <input type="submit" name="em_rsvp_submit" value="Confirm Attendance">
            </p>
        </form>
    </div>

    <?php

    return $content . ob_get_clean();
}

add_filter('the_content', 'em_display_rsvp_form');

function em_handle_rsvp_submission() {

    if (!isset($_POST['em_rsvp_submit'])) {
        return;
    }

    if (!isset($_POST['em_rsvp_nonce']) ||
        !wp_verify_nonce($_POST['em_rsvp_nonce'], 'em_rsvp_action')) {
        return;
    }

    if (!is_singular('event')) {
        return;
    }

    $post_id = get_the_ID();

    $name  = sanitize_text_field($_POST['em_rsvp_name']);
    $email = sanitize_email($_POST['em_rsvp_email']);

    if (empty($name) || empty($email)) {
        return;
    }

    $attendees = get_post_meta($post_id, '_event_attendees', true);

    if (!is_array($attendees)) {
        $attendees = array();
    }

    $attendees[] = array(
        'name'  => $name,
        'email' => $email,
        'time'  => current_time('mysql'),
    );

    update_post_meta($post_id, '_event_attendees', $attendees);
}

add_action('template_redirect', 'em_handle_rsvp_submission');

function em_add_event_meta_to_rest() {

    register_rest_field(
        'event',
        'event_date',
        array(
            'get_callback' => function($post) {
                return get_post_meta($post['id'], '_event_date', true);
            },
            'schema' => array(
                'type' => 'string',
            ),
        )
    );

    register_rest_field(
        'event',
        'event_location',
        array(
            'get_callback' => function($post) {
                return get_post_meta($post['id'], '_event_location', true);
            },
            'schema' => array(
                'type' => 'string',
            ),
        )
    );

}
register_rest_field(
    'event',
    'attendee_count',
    array(
        'get_callback' => function($post) {

            $attendees = get_post_meta($post['id'], '_event_attendees', true);

            if (is_array($attendees)) {
                return count($attendees);
            }

            return 0;
        },
        'schema' => array(
            'type' => 'integer',
        ),
    )
);


add_action('rest_api_init', 'em_add_event_meta_to_rest');

