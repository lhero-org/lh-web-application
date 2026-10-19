<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Scheduled work: the one-off run after activation (rewrite rules, icon sizes) and the rewarm of
 * the service worker, manifest, start URL, offline page and home page after a page cache clear.
 * Scheduling on activation and clearing on deactivation stay in the main class.
 */
class LH_web_application_cron_class extends LH_web_application_plugin {

    private static $instance;

    public function run_initial_processes(){

        flush_rewrite_rules();

        // Make sure the manifest icon has the sizes the manifest needs.
        $icon_id = self::return_manifest_icon_id();

        if ( empty( $icon_id ) || ! wp_attachment_is_image( $icon_id ) ) {

            return;

        }

        $fullsizepath = get_attached_file( $icon_id );

        if ( empty( $fullsizepath ) || ! file_exists( $fullsizepath ) ) {

            return;

        }

        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {

            require_once ABSPATH . 'wp-admin/includes/image.php';

        }

        wp_update_attachment_metadata( $icon_id, wp_generate_attachment_metadata( $icon_id, $fullsizepath ) );

    }

    public function get_the_single_url( $arg_1_is_a_url ) {

        wp_clear_scheduled_hook( self::return_plugin_namespace().'_ping_single_url' ,  array($arg_1_is_a_url));

        $args = array(
            'redirection' => 1,
            'blocking'    => false,
        );

        wp_remote_get($arg_1_is_a_url, $args);

    }

    public function reprocess(){

        wp_schedule_single_event( time() + wp_rand( 0, 3600 ), self::return_plugin_namespace().'_ping_single_url', array(  home_url('/'.self::return_sw_file_name() ))  );
        wp_schedule_single_event( time() + wp_rand( 0, 3600 ), self::return_plugin_namespace().'_ping_single_url', array( self::return_manifest_url() ) );
        wp_schedule_single_event( time() + wp_rand( 0, 3600 ), self::return_plugin_namespace().'_ping_single_url', array( self::return_start_url() ) );
        wp_schedule_single_event( time() + wp_rand( 0, 3600 ), self::return_plugin_namespace().'_ping_single_url', array( self::return_offline_page_url() ) );
        wp_schedule_single_event( time() + wp_rand( 0, 3600 ), self::return_plugin_namespace().'_ping_single_url', array( home_url('/') ) );

    }

    public static function get_instance(){

        if ( null === self::$instance ) {

            self::$instance = new self();

        }

        return self::$instance;

    }

    public function __construct() {

        // the one-off run scheduled on activation
        add_action( 'lh_web_application_initial_run', array( $this, 'run_initial_processes' ) );

        // rewarm the endpoints after a page cache clear
        add_action( 'wp_cache_cleared', array( $this, 'reprocess' ) );

        // the one-off events that fetch a single URL
        add_action( self::return_plugin_namespace() . '_ping_single_url', array( $this, 'get_the_single_url' ), 10, 1 );

    }

}

LH_web_application_cron_class::get_instance();
