# Event Manager Plugin

A custom WordPress Event Management plugin built using WordPress core
APIs and best development practices.\
This plugin provides event creation, categorization, RSVP functionality,
REST API integration, and secure data handling.

------------------------------------------------------------------------

# 1. Project Overview

Event Manager is a custom WordPress plugin that allows administrators
to:

-   Create and manage events
-   Categorize events using custom taxonomy
-   Store event date and location as meta fields
-   Allow users to RSVP
-   Expose event data through REST API
-   Maintain secure and sanitized data handling

------------------------------------------------------------------------

# 2. Features Implemented

## 2.1 Custom Post Type (CPT)

-   Post type: `event`
-   Publicly accessible
-   Archive enabled
-   Slug: `/events/`
-   Supports:
    -   Title
    -   Editor
    -   Featured Image
-   REST enabled (`show_in_rest => true`)

------------------------------------------------------------------------

## 2.2 Custom Taxonomy

-   Taxonomy: `event_category`
-   Hierarchical (like default categories)
-   Assigned to `event` post type
-   Used for filtering in shortcode
-   Example term: `conference`

------------------------------------------------------------------------

## 2.3 Custom Meta Fields

Each event stores:

-   `_event_date`
-   `_event_location`

Stored securely in `wp_postmeta`.

------------------------------------------------------------------------

## 2.4 Secure Meta Saving

The plugin implements:

-   `wp_nonce_field()`
-   `wp_verify_nonce()`
-   `sanitize_text_field()`
-   Escaped output using `esc_html()`

------------------------------------------------------------------------

## 2.5 Shortcode Implementation

Shortcode:

    [event_list]

With filtering:

    [event_list event_type="conference"]

Uses `WP_Query` to display published events.

------------------------------------------------------------------------

## 2.6 RSVP Functionality

-   RSVP form submission
-   Nonce validation
-   Sanitized user input
-   Data stored in `_event_attendees`
-   Email notification on submission

------------------------------------------------------------------------

## 2.7 REST API Integration

Endpoint:

    /wp-json/wp/v2/event

Custom REST fields:

-   `event_date`
-   `event_location`
-   `attendee_count`

Example:

    {
      "id": 47,
      "event_date": "2026-04-15",
      "event_location": "Hyderabad",
      "attendee_count": 2
    }

------------------------------------------------------------------------

# 3. Database Structure

Uses native WordPress tables:

-   `wp_posts`
-   `wp_postmeta`

No custom tables created.

------------------------------------------------------------------------

# 4. Installation

1.  Upload plugin to `/wp-content/plugins/`
2.  Activate plugin
3.  Create events
4.  Assign categories
5.  Use shortcode
6.  Access REST endpoint

------------------------------------------------------------------------

# 5. Security Measures

-   Nonce verification (CSRF protection)
-   Input sanitization
-   Escaped output
-   Controlled REST exposure

------------------------------------------------------------------------

# 6. Unit Testing Scenarios

## CPT Tests

-   Verify `event` post type exists
-   Confirm archive works
-   Confirm REST endpoint works

## Meta Tests

-   Verify `_event_date` saves correctly
-   Verify `_event_location` saves correctly
-   Confirm sanitized storage

## RSVP Tests

-   Valid RSVP submission
-   Invalid nonce rejection
-   Attendee count increments

## REST Tests

-   Confirm custom fields appear
-   Confirm attendee_count appears
-   Confirm attendee list is not exposed

------------------------------------------------------------------------

# 7. Public Repository Setup

Initialize Git:

    git init
    git add .
    git commit -m "Initial commit - Event Manager Plugin"

Push to GitHub:

    git remote add origin https://github.com/yourusername/event-manager.git
    git branch -M main
    git push -u origin main

------------------------------------------------------------------------

# Conclusion

This plugin demonstrates production-level WordPress development
including:

-   Custom Post Types
-   Taxonomies
-   Secure Meta Handling
-   Shortcodes
-   RSVP System
-   REST API Customization
-   Security Best Practices
