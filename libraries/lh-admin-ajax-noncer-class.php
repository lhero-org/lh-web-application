<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Serves REST API nonces to the service worker.
 */
class LH_Admin_ajax_noncer_class {

    private static $instance;

    /**
     * Returns a fresh REST API nonce for the current visitor as a JSON string.
     *
     * Service workers have no page to read a nonce from, so their includes
     * (e.g. LH Web Push Notifications, LH BuddyPress Live Content) fetch one here
     * via the admin_ajax_return_wp_nonce variable and send it as X-WP-Nonce.
     * Only the wp_rest nonce is issued: any nonce_name parameter is ignored.
     */
    public function return_wp_nonce() {

        wp_send_json( wp_create_nonce( 'wp_rest' ) );

    }

    /**
     * Gets an instance of our plugin.
     *
     * using the singleton pattern
     */
    public static function get_instance(){

        if ( null === self::$instance ) {

            self::$instance = new self();

        }

        return self::$instance;

    }

    private function __construct() {

        add_action( 'wp_ajax_return_wp_nonce', array( $this, 'return_wp_nonce' ) );
        add_action( 'wp_ajax_nopriv_return_wp_nonce', array( $this, 'return_wp_nonce' ) );

    }

}

LH_Admin_ajax_noncer_class::get_instance();
