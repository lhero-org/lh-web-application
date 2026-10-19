<?php
/**
 * Plugin Name: LH Web Application
 * Description: Make your WordPress sites into a modern web application.
 * Plugin URI: https://lhero.org/portfolio/lh-web-application/
 * Version: 1.38.1
 * Author: Peter Shaw
 * Text Domain: lh-web-application
 * Author URI: https://shawfactor.com
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Shared by every class below, and needed on activation and uninstall, before plugin_init() runs.
require_once plugin_dir_path( __FILE__ ) . 'includes/lh-web-application-options-trait.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/lh-web-application-images-trait.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/lh-web-application-scripts-trait.php';


/**
* LH Web Application plugin class
*
* Identity, loading and lifecycle. The work is done by the classes in includes/, which extend this
* one; the option names, image helpers and script helpers they share come from the traits above.
*/
class LH_web_application_plugin {

    use LH_web_application_options_trait;
    use LH_web_application_images_trait;
    use LH_web_application_scripts_trait;

    private static $instance;

    static function return_plugin_namespace(){

        return 'lh_web_application';

    }

    static function return_plugin_text_domain(){

        return 'lh-web-application';

    }

    static function plugin_name(){

        return __('LH Web Application', self::return_plugin_text_domain());

    }

    // Read from the plugin header, so the header stays the single source of truth.
    static function plugin_version(){

        static $version = null;

        if ( null === $version ) {

            $data    = get_file_data( __FILE__, array( 'Version' => 'Version' ), 'plugin' );
            $version = $data['Version'];

        }

        return $version;

    }

    static function return_file_name(){

        return plugin_basename( __FILE__ );

    }

    // Asset URLs and versions, relative to the plugin folder.
    static function return_asset_url( $path ){

        return plugins_url( $path, __FILE__ );

    }

    static function return_asset_version( $path ){

        $file = plugin_dir_path( __FILE__ ) . $path;

        return file_exists( $file ) ? (string) filemtime( $file ) : self::plugin_version();

    }

    static function write_log( $log, $level = 'info' ) {

        if ( ! defined( 'WP_DEBUG' ) || true !== WP_DEBUG ) {

            return;

        }

        if ( ! apply_filters( self::return_plugin_namespace() . '_return_plugin_debugging_status', true ) ) {

            return;

        }

        if ( is_array( $log ) || is_object( $log ) ) {

            $message = wp_json_encode( $log );

            if ( false === $message ) {

                $message = preg_replace( '/\s+/', ' ', print_r( $log, true ) );

            }

        } else {

            $message = (string) $log;

        }

        error_log( ucfirst( $level ) . ':  [' . self::return_plugin_namespace() . '] ' . $message );

    }

    static function setup_crons(){

        wp_clear_scheduled_hook( 'lh_web_application_initial_run' );
        wp_clear_scheduled_hook( self::return_plugin_namespace().'_ongoing' );
        wp_schedule_single_event(time() + wp_rand( 0, 60 ), 'lh_web_application_initial_run');

    }

    static function remove_crons(){

        wp_clear_scheduled_hook( 'lh_web_application_initial_run' );
        wp_clear_scheduled_hook( self::return_plugin_namespace().'_ongoing' );

    }

    public function plugin_init(){

        if ( ! apply_filters( self::return_plugin_namespace() . '_init_plugin', true ) ) {

            return;

        }

        $includes = plugin_dir_path( __FILE__ ) . 'includes/';

        require_once plugin_dir_path( __FILE__ ) . 'libraries/lh-admin-ajax-noncer-class.php';
        require_once $includes . 'lh-web-application-settings-class.php';
        require_once $includes . 'lh-web-application-app-install-class.php';
        require_once $includes . 'lh-web-application-install-block-class.php';
        require_once $includes . 'lh-web-application-service-worker-class.php';
        require_once $includes . 'lh-web-application-manifest-class.php';
        require_once $includes . 'lh-web-application-head-meta-class.php';
        require_once $includes . 'lh-web-application-menu-icon-class.php';
        require_once $includes . 'lh-web-application-offline-page-class.php';
        require_once $includes . 'lh-web-application-cron-class.php';

        // add the data attributes the classes ask for to their script tags
        add_filter( 'script_loader_tag', array( $this, 'filter_script_loader_tag' ), 10, 3 );

    }


    /**
     * Gets an instance of our plugin.
     *
     * using the singleton pattern
    */

    public static function get_instance(){

        if (null === self::$instance) {

            self::$instance = new self();

        }

        return self::$instance;

    }



    static function on_activate($network_wide) {

        if ( is_multisite() && $network_wide ) {

            $args = array('number' => 500, 'fields' => 'ids');
            $sites = get_sites($args);

            foreach ($sites as $blog_id) {

                switch_to_blog($blog_id);
                self::setup_crons();
                restore_current_blog();

            }

        } else {

            self::setup_crons();

        }

    }

    static function on_deactivate($network_wide) {

        if ( is_multisite() && $network_wide ) {

            $args = array('number' => 500, 'fields' => 'ids');
            $sites = get_sites($args);

            foreach ($sites as $blog_id) {

                switch_to_blog($blog_id);
                self::remove_crons();
                restore_current_blog();

            }

        } else {

            self::remove_crons();

        }

    }



    /**
     * Removes the plugin's settings, scheduled events and menu icon meta from
     * every site when the plugin is deleted. Uploaded images are left alone.
     */
    static function uninstall() {

        $blog_ids = is_multisite() ? get_sites(array('number' => 0, 'fields' => 'ids')) : array(get_current_blog_id());

        foreach ($blog_ids as $blog_id) {

            if (is_multisite()) {

                switch_to_blog($blog_id);

            }

            self::remove_crons();
            delete_option(self::return_opt_name());
            delete_option(self::return_manifest_icon_attachment_id_name());
            delete_post_meta_by_key('_'.self::return_menu_icon_name().'-attachment_id');

            if (is_multisite()) {

                restore_current_blog();

            }

        }

    }



    private function __construct() {

        //run whatever on plugins loaded
        add_action( 'plugins_loaded', array( $this, 'plugin_init' ) );

    }

}

LH_web_application_plugin::get_instance();
register_activation_hook( __FILE__, array( 'LH_web_application_plugin', 'on_activate' ) );
register_deactivation_hook( __FILE__, array( 'LH_web_application_plugin', 'on_deactivate' ) );
register_uninstall_hook( __FILE__, array( 'LH_web_application_plugin', 'uninstall' ) );
