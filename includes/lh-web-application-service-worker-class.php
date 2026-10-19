<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The service worker served at /serviceworker.script (printed by partials/sw4.php), and the page
 * script that registers it.
 */
class LH_web_application_service_worker_class extends LH_web_application_plugin {

    private static $instance;

    static function get_import_scripts_array() {

        $handles = array( self::return_plugin_namespace() . '-fetch_handler' );

        $handles = apply_filters(self::return_plugin_namespace().'_import_scripts_handle_filter', $handles);

        $scripts_array = array();

        foreach ( $handles as $handle ) {

            if ($src = self::get_script_src_by_handle($handle)){

                $scripts_array[] = $src;

            }

        }

        return $scripts_array;

    }

    static function return_precache_static_urls_as_array(){

        global $wp_scripts;

        $precache_static_urls = array();

        foreach( $wp_scripts->queue as $script ) {

            if (!empty($wp_scripts->registered[$script]->src) && wp_http_validate_url($wp_scripts->registered[$script]->src)){

                $precache_static_urls[] = apply_filters('script_loader_src', add_query_arg('ver', $wp_scripts->registered[$script]->ver, $wp_scripts->registered[$script]->src), $script);

            }

        }

        global $wp_styles;

        foreach( $wp_styles->queue as $style ) {

            if (!empty($wp_styles->registered[$style]->src) && wp_http_validate_url($wp_styles->registered[$style]->src)){

                $precache_static_urls[] = apply_filters('style_loader_src', add_query_arg('ver', $wp_styles->registered[$style]->ver, $wp_styles->registered[$style]->src), $style);

            }

        }

        return apply_filters(self::return_plugin_namespace().'_precache_static_urls_filter', $precache_static_urls);

    }

    /**
     * Where the floating Back button shows: ios (installed app on iPhone and iPad, the default,
     * since Android has a system back button), standalone (any installed app) or none.
     */
    static function return_back_button_mode() {

        $mode = apply_filters( self::return_plugin_namespace() . '_back_button', 'ios' );

        return in_array( $mode, array( 'ios', 'standalone', 'none' ), true ) ? $mode : 'ios';

    }

    public function register_scripts_and_styles() {

        if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || defined( 'XMLRPC_REQUEST' ) || wp_doing_cron() || is_robots() ) {

            return;

        }

        $main_handle = self::return_plugin_namespace() . '-script';
        $main_path   = 'assets/scripts/lh-web-application-main.js';

        wp_register_script( $main_handle, self::return_asset_url( $main_path ), array(), self::return_asset_version( $main_path ), array( 'in_footer' => true, 'strategy' => 'defer' ) );

        self::add_script_attributes( $main_handle, array(
            'id'                          => self::return_plugin_namespace() . '-main-js',
            'data-sw-url'                 => home_url( '/' . self::return_sw_file_name() ),
            /* translators: %s: when the saved copy of the page was made, e.g. Sat 17 Oct, 4:12 pm */
            'data-offline-notice'         => __( 'You are offline. This is a saved copy from %s.', self::return_plugin_text_domain() ),
            'data-offline-notice-undated' => __( 'You are offline. This is a saved copy.', self::return_plugin_text_domain() ),
            'data-online-notice'          => __( 'You are back online.', self::return_plugin_text_domain() ),
            'data-reload-label'           => __( 'Reload', self::return_plugin_text_domain() ),
            'data-back-label'             => __( 'Back', self::return_plugin_text_domain() ),
            'data-back-button'            => self::return_back_button_mode(),
        ) );

        wp_enqueue_script( $main_handle );

        // Registered only so the service worker can import it (see get_import_scripts_array()).
        $fetch_path = 'assets/scripts/fetch-handler.js';

        wp_register_script( self::return_plugin_namespace() . '-fetch_handler', self::return_asset_url( $fetch_path ), array(), self::return_asset_version( $fetch_path ), true );

    }

    public function add_query_vars( $vars ){

        $vars[] = '__lh_web_application-serviceworker';

        return $vars;

    }

    public function add_endpoint(){

        add_rewrite_rule('serviceworker\.script$','index.php?__lh_web_application-serviceworker=1','top');

    }

    public function cache_request_anything( $eof_pattern){

        global $wp_super_cache_comments;

        // Don't append HTML comments to the JSON output, because that would invalidate it
        $wp_super_cache_comments = false;

        $eof_pattern = '(.*?)';

        return $eof_pattern;

    }

    public function maybe_serve_service_worker(){

        global $wp;
        global $wp_super_cache_comments;

        if ( isset( $wp->query_vars['__lh_web_application-serviceworker'] ) ) {

            $wp_super_cache_comments = false;
            add_filter( 'wp_cache_eof_tags', array( $this, 'cache_request_anything' ) );

            header( 'Content-Type: application/javascript; charset=utf-8' );

            // The template escapes everything it prints.
            include plugin_dir_path( __DIR__ ) . 'partials/sw4.php';

            do_action( 'shutdown' );

            exit;

        }

    }

    public function prevent_auth_on_service_worker($user_id){

        return false;

    }

    public static function get_instance(){

        if ( null === self::$instance ) {

            self::$instance = new self();

        }

        return self::$instance;

    }

    public function __construct() {

        // The service worker is the same for everyone, so never authenticate its request.
        $request_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';

        if ( ! empty( $request_path ) && ( basename( $request_path ) == self::return_sw_file_name() ) && ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() ) {

            add_filter( 'determine_current_user', array( $this, 'prevent_auth_on_service_worker' ), 100, 1 );

        }

        add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
        add_action( 'init', array( $this, 'add_endpoint' ), 0 );
        add_action( 'wp', array( $this, 'maybe_serve_service_worker' ), PHP_INT_MAX );

        // The page script, which registers the worker, and the fetch handler the worker imports.
        add_action( 'wp_loaded', array( $this, 'register_scripts_and_styles' ), 10 );

    }

}

LH_web_application_service_worker_class::get_instance();
