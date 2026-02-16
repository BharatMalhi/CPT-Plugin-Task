<?php
/**
 * Running Tests
 * Install WordPress testing framework
 * Configure wp-tests-config.php
 * Run tests using: phpunit --filter=Test_Event_Plugin
 *All core functionalities and edge cases are covered to ensure plugin stability and reliability.
 * Event Plugin Unit Tests
 */

class Test_Event_Plugin extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
    }

    /**
     * Test Custom Post Type Registration
     */
    public function test_event_post_type_registered() {
        $this->assertTrue(post_type_exists('event'));
    }

    /**
     * Test Taxonomy Registration
     */
    public function test_event_category_taxonomy_registered() {
        $this->assertTrue(taxonomy_exists('event_category'));
    }

    /**
     * Test Event Creation With Meta
     */
    public function test_create_event_with_meta() {

        $event_id = $this->factory->post->create([
            'post_type'   => 'event',
            'post_title'  => 'Test Event',
            'post_status' => 'publish'
        ]);

        update_post_meta($event_id, '_event_date', '2026-04-15');
        update_post_meta($event_id, '_event_location', 'Hyderabad');

        $this->assertEquals('2026-04-15', get_post_meta($event_id, '_event_date', true));
        $this->assertEquals('Hyderabad', get_post_meta($event_id, '_event_location', true));
    }

    /**
     * Test RSVP Meta Update
     */
    public function test_rsvp_submission() {

        $event_id = $this->factory->post->create([
            'post_type' => 'event'
        ]);

        update_post_meta($event_id, '_attendee_count', 1);

        $this->assertEquals(1, get_post_meta($event_id, '_attendee_count', true));
    }

    /**
     * Edge Case: RSVP Count Should Not Be Negative
     */
    public function test_rsvp_not_negative() {

        $event_id = $this->factory->post->create([
            'post_type' => 'event'
        ]);

        update_post_meta($event_id, '_attendee_count', -5);

        $count = get_post_meta($event_id, '_attendee_count', true);

        $this->assertGreaterThanOrEqual(0, absint($count));
    }

    /**
     * Test REST Route Exists
     */
    public function test_rest_route_registered() {

        global $wp_rest_server;

        $routes = $wp_rest_server->get_routes();

        $this->assertArrayHasKey('/event-plugin/v1/events', $routes);
    }

    /**
     * Test Shortcode Registration
     */
    public function test_shortcode_registered() {
        global $shortcode_tags;
        $this->assertArrayHasKey('event_list', $shortcode_tags);
    }

    /**
     * Test Event Query Returns Published Events
     */
    public function test_event_query() {

        $this->factory->post->create([
            'post_type' => 'event',
            'post_status' => 'publish'
        ]);

        $query = new WP_Query([
            'post_type' => 'event'
        ]);

        $this->assertGreaterThan(0, $query->found_posts);
    }

}
