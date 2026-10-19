<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The tags in the document head: viewport, theme colour, the manifest link, Apple touch icons,
 * iOS startup images and the Apple web app tags. Also the image sub-sizes those icons need.
 */
class LH_web_application_head_meta_class extends LH_web_application_plugin {

    private static $instance;

    public function add_meta_to_head() {

        global $wp;

        $options = get_option( self::return_opt_name() );

        $apple_web_app_capable = ! empty( $options[ self::return_apple_web_app_capable_field_name() ] ) && ( 1 == $options[ self::return_apple_web_app_capable_field_name() ] );

        echo "\n<!-- Start LH Web Application Meta -->\n";
        echo '<meta name="viewport" content="viewport-fit=cover,width=device-width, initial-scale=1.0" />' . "\n";

        // Lets the page script clear the offline pages cache when the signed-in user changes.
        $user_marker = is_user_logged_in() ? substr( wp_hash( self::return_plugin_namespace() . '-user-' . get_current_user_id() ), 0, 16 ) : '';
        echo '<meta name="lh-web-application-user" content="' . esc_attr( $user_marker ) . '" />' . "\n";

        if ( $apple_web_app_capable ) {

            // Chrome reads the unprefixed tag; iOS shows startup images only with the Apple one.
            echo '<meta name="mobile-web-app-capable" content="yes" />' . "\n";
            echo '<meta name="apple-mobile-web-app-capable" content="yes" />' . "\n";

        }

        if ( ! empty( $options[ self::return_theme_color_field_name() ] ) ) {

            echo '<meta name="theme-color" content="' . esc_attr( $options[ self::return_theme_color_field_name() ] ) . '" />' . "\n";

        }

        if ( $apple_web_app_capable ) {

            echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />' . "\n";

        }

        echo '<link rel="manifest" href="' . esc_url( self::return_manifest_url() ) . '" crossorigin="use-credentials" />' . "\n";
        echo '<link rel="prefetch" href="' . esc_url( self::return_manifest_url() ) . '" crossorigin="use-credentials" />' . "\n";

        foreach ( self::create_apple_icon_sizes() as $apple_size ) {

            if ( $href = self::check_image_size( self::return_ios_icon_id(), self::return_ios_icon_name(), $apple_size['width'], $apple_size['height'] ) ) {

                echo '<link rel="apple-touch-icon" sizes="' . esc_attr( $apple_size['width'] . 'x' . $apple_size['height'] ) . '" href="' . esc_url( $href ) . '" />' . "\n";

            }

        }

        if ( self::return_ios_startup_id() ) {

            foreach ( self::create_apple_startup_sizes() as $startup_size ) {

                if ( $href = self::return_startup_image_url( self::return_ios_startup_id(), $startup_size['width'], $startup_size['height'] ) ) {

                    echo '<link rel="apple-touch-startup-image" href="' . esc_url( $href ) . '" media="' . esc_attr( $startup_size['media'] ) . '" />' . "\n";

                }

            }

        }

        echo "<!-- End LH Web Application Meta -->\n\n";

        // On the offline page, go back to the site as soon as the connection returns.
        if ( strtolower( $wp->request ) == self::return_offline_page_slug() ) {

            $script = '(function() {
    var offline_url = ' . wp_json_encode( self::return_offline_page_url() ) . ';
    var home_url = ' . wp_json_encode( home_url() ) . ';
    function do_redirect() {
        if (!navigator.onLine) {
            return;
        }
        if (window.location.href.indexOf(offline_url) > -1) {
            window.location.replace(home_url);
        } else {
            window.location.reload();
        }
    }
    do_redirect();
    window.addEventListener("online", do_redirect);
})();';

            wp_print_inline_script_tag( $script );

        }

    }

    /**
     * Block templates make core print a viewport tag of its own, without viewport-fit=cover, just
     * before the plugin's. With two tags iOS may not apply viewport-fit=cover, so the safe-area
     * insets read 0 and anything placed with them hides under the status bar. Runs before core's
     * priority 0 callback, which locate_block_template() has already added by now.
     */
    public function remove_core_viewport_meta() {

        remove_action( 'wp_head', '_block_template_viewport_meta_tag', 0 );

    }

    public function add_subsizes($new_sizes, $image_meta, $attachment_id){

        if ($attachment_id == self::return_manifest_icon_id()){

            foreach( self::create_manifest_icon_sizes() as $size ){

                $new_sizes[self::return_manifest_icon_name().'_'.$size['width'].'x'.$size['height']]['width'] = $size['width'];
                $new_sizes[self::return_manifest_icon_name().'_'.$size['width'].'x'.$size['height']]['height'] = $size['height'];
                $new_sizes[self::return_manifest_icon_name().'_'.$size['width'].'x'.$size['height']]['crop'] = 1;

            }

        }


        if ($attachment_id == self::return_ios_icon_id()){

            foreach( self::create_apple_icon_sizes() as $apple_icon_size ){

                $new_sizes[self::return_ios_icon_name().'_'.$apple_icon_size['width'].'x'.$apple_icon_size['height']]['width'] = $apple_icon_size['width'];
                $new_sizes[self::return_ios_icon_name().'_'.$apple_icon_size['width'].'x'.$apple_icon_size['height']]['height'] = $apple_icon_size['height'];
                $new_sizes[self::return_ios_icon_name().'_'.$apple_icon_size['width'].'x'.$apple_icon_size['height']]['crop'] = 1;

            }

        }

        if ($attachment_id == self::return_ios_startup_id()){

            foreach( self::create_apple_startup_sizes() as $apple_startup_size ){

                $new_sizes[self::return_ios_startup_name().'_'.$apple_startup_size['width'].'x'.$apple_startup_size['height']]['width'] = $apple_startup_size['width'];
                $new_sizes[self::return_ios_startup_name().'_'.$apple_startup_size['width'].'x'.$apple_startup_size['height']]['height'] = $apple_startup_size['height'];
                $new_sizes[self::return_ios_startup_name().'_'.$apple_startup_size['width'].'x'.$apple_startup_size['height']]['crop'] = 1;

            }

        }

        return $new_sizes;

    }

    // Drop the theme's precomposed Apple icon, which the touch icons above replace.
    public function theme_metatags($meta_tags) {

        $return = array();

        foreach( $meta_tags as $meta_tag ) {

            if (strpos($meta_tag, 'apple-touch-icon-precomposed') === false) {

                $return[] = $meta_tag;

            }

        }

        return $return;

    }

    public static function get_instance(){

        if ( null === self::$instance ) {

            self::$instance = new self();

        }

        return self::$instance;

    }

    public function __construct() {

        // add the meta tags to the head of the document, as the only viewport tag
        add_action( 'wp_head', array( $this, 'remove_core_viewport_meta' ), -1 );
        add_action( 'wp_head', array( $this, 'add_meta_to_head' ), 1 );
        add_action( 'embed_head', array( $this, 'add_meta_to_head' ), 1 );
        add_action( 'admin_head', array( $this, 'add_meta_to_head' ), 1 );

        // add the icon sizes the manifest and apple icons need
        add_filter( 'intermediate_image_sizes_advanced', array( $this, 'add_subsizes' ), 10, 3 );

        // remove redundant theme meta tags
        add_filter( 'site_icon_meta_tags', array( $this, 'theme_metatags' ) );

    }

}

LH_web_application_head_meta_class::get_instance();
