<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The per-site Settings > Web Application screen, and the icon picker for the
 * shortcut menu's items on Appearance > Menus.
 */
class LH_web_application_settings_class extends LH_web_application_plugin {

    private static $instance;

    static function return_display_modes(){

        return array( 'browser', 'fullscreen', 'standalone', 'minimal-ui' );

    }

    public function enqueue_admin_scripts(){

        wp_enqueue_style( 'wp-color-picker' );

        wp_enqueue_script( self::return_plugin_namespace() . '-colour_picker', self::return_asset_url( 'assets/scripts/admin-colourpicker.js' ), array( 'wp-color-picker', 'jquery' ), self::return_asset_version( 'assets/scripts/admin-colourpicker.js' ), true );

        $this->enqueue_uploader();

    }

    public function enqueue_uploader(){

        wp_enqueue_media();

        wp_enqueue_script( self::return_plugin_namespace() . '-dashboard-admin', self::return_asset_url( 'assets/scripts/admin-uploader.js' ), array( 'jquery' ), self::return_asset_version( 'assets/scripts/admin-uploader.js' ), true );

    }

    // The shortcut menu's items get an icon picker on Appearance > Menus.
    public function maybe_enqueue_menu_uploader( $hook_suffix ){

        if ( 'nav-menus.php' === $hook_suffix ) {

            $this->enqueue_uploader();

        }

    }

    public function load_admin_scripts(){

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

    }

    // Settings are per site, so site admins manage them.
    public function plugin_menu() {

        $menu = add_options_page( self::plugin_name(), __( 'Web Application', self::return_plugin_text_domain() ), 'manage_options', self::return_file_name(), array( $this, 'plugin_options' ) );

        // Load the JS conditionally
        add_action( 'load-' . $menu, array( $this, 'load_admin_scripts' ) );

    }

    static function handle_options_update(){

        $options = get_option( self::return_opt_name() );

        if ( ! is_array( $options ) ) {

            $options = array();

        }

        $nonce_name = self::return_plugin_namespace() . '-nonce';

        if ( empty( $_POST[ $nonce_name ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ $nonce_name ] ) ), $nonce_name ) ) {

            return $options;

        }

        if ( ! current_user_can( 'manage_options' ) ) {

            return $options;

        }

        // The plain text fields, read only once the nonce and capability have been checked.
        $posted = array();

        foreach ( array(
            self::return_short_name_field_name(),
            self::return_manifest_icon_attachment_id_name(),
            self::return_ios_icon_attachment_id_name(),
            self::return_ios_startup_attachment_id_name(),
            self::return_is_maskable_icon_name(),
            self::return_shortcut_menu_name(),
            self::return_display_mode_field_name(),
            self::return_apple_web_app_capable_field_name(),
            self::return_theme_color_field_name(),
            self::return_background_color_field_name(),
            self::return_install_prompt_field_name(),
            self::return_install_reason_field_name(),
        ) as $field ) {

            $posted[ $field ] = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';

        }

        $short_name = $posted[ self::return_short_name_field_name() ];

        if ( '' !== $short_name ) {

            $options[ self::return_short_name_field_name() ] = $short_name;

        } else {

            unset( $options[ self::return_short_name_field_name() ] );

        }

        foreach ( array( self::return_manifest_icon_attachment_id_name(), self::return_ios_icon_attachment_id_name(), self::return_ios_startup_attachment_id_name() ) as $field ) {

            $attachment_id = absint( $posted[ $field ] );

            if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {

                $options[ $field ] = $attachment_id;

            } else {

                unset( $options[ $field ] );

            }

        }

        $options[ self::return_is_maskable_icon_name() ] = ( '1' === $posted[ self::return_is_maskable_icon_name() ] ) ? '1' : '0';

        $shortcut_menu = sanitize_title( $posted[ self::return_shortcut_menu_name() ] );

        if ( '' !== $shortcut_menu && '0' !== $shortcut_menu ) {

            $options[ self::return_shortcut_menu_name() ] = $shortcut_menu;

        } else {

            unset( $options[ self::return_shortcut_menu_name() ] );

        }

        $display_mode = $posted[ self::return_display_mode_field_name() ];

        if ( in_array( $display_mode, self::return_display_modes(), true ) ) {

            $options[ self::return_display_mode_field_name() ] = $display_mode;

        }

        $apple_web_app_capable = $posted[ self::return_apple_web_app_capable_field_name() ];

        if ( in_array( $apple_web_app_capable, array( '0', '1' ), true ) ) {

            $options[ self::return_apple_web_app_capable_field_name() ] = $apple_web_app_capable;

        }

        // An emptied colour field falls back to the default again.
        foreach ( array( self::return_theme_color_field_name(), self::return_background_color_field_name() ) as $field ) {

            $colour = sanitize_hex_color( $posted[ $field ] );

            if ( ! empty( $colour ) ) {

                $options[ $field ] = $colour;

            } else {

                unset( $options[ $field ] );

            }

        }

        $install_prompt = $posted[ self::return_install_prompt_field_name() ];

        if ( in_array( $install_prompt, self::return_install_prompt_audiences(), true ) ) {

            $options[ self::return_install_prompt_field_name() ] = $install_prompt;

        }

        // An emptied reason falls back to the default wording again.
        $install_reason = $posted[ self::return_install_reason_field_name() ];

        if ( '' !== $install_reason ) {

            $options[ self::return_install_reason_field_name() ] = $install_reason;

        } else {

            unset( $options[ self::return_install_reason_field_name() ] );

        }

        $offline_message = isset( $_POST[ self::return_offline_message_field_name() ] ) ? trim( wp_kses_post( wp_unslash( $_POST[ self::return_offline_message_field_name() ] ) ) ) : '';

        if ( '' !== $offline_message ) {

            $options[ self::return_offline_message_field_name() ] = $offline_message;

        } else {

            unset( $options[ self::return_offline_message_field_name() ] );

        }

        update_option( self::return_opt_name(), $options );

        echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'Settings saved.', self::return_plugin_text_domain() ) . '</strong></p></div>' . "\n";

        return $options;

    }


    public function plugin_options() {

        if ( ! current_user_can( 'manage_options' ) ) {

            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', self::return_plugin_text_domain() ) );

        }

        $options = self::handle_options_update();

        // Now display the settings editing screen
        include plugin_dir_path( __DIR__ ) . 'partials/option-settings.php';

    }

    public static function get_instance(){

        if ( null === self::$instance ) {

            self::$instance = new self();

        }

        return self::$instance;

    }


    public function __construct() {

        //add backend options page
        add_action( 'admin_menu', array( $this, 'plugin_menu' ) );

        //icon picker for shortcut menu items
        add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_menu_uploader' ) );

    }

}

LH_web_application_settings_class::get_instance();
