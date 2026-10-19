<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The Shortcut icon field on the shortcut menu's items (Appearance > Menus), stored in
 * _lh_web_application-menu_icon-attachment_id post meta and used for the manifest shortcuts.
 */
class LH_web_application_menu_icon_class extends LH_web_application_plugin {

    private static $instance;

    public function add_menu_icon_uploader( $item_id, $item, $depth, $args, $id ){

        global $nav_menu_selected_id;

        $options = get_option( self::return_opt_name() );

        if ( empty( $options[ self::return_shortcut_menu_name() ] ) ) {

            return;

        }

        $menu = get_term_by( 'slug', $options[ self::return_shortcut_menu_name() ], 'nav_menu' );

        if ( empty( $menu->term_id ) || empty( $nav_menu_selected_id ) || ( $menu->term_id != $nav_menu_selected_id ) ) {

            return;

        }

        $attachment_id = absint( get_post_meta( $item_id, '_' . self::return_menu_icon_name() . '-attachment_id', true ) );
        $prefix        = self::return_menu_icon_name() . '-' . $item_id;

        wp_nonce_field( self::return_menu_icon_name() . '-nonce', $prefix . '-nonce', false );

        echo '<p class="description description-wide">' . "\n";
        echo '<label for="' . esc_attr( $prefix . '-attachment_url' ) . '">' . esc_html__( 'Shortcut icon:', self::return_plugin_text_domain() ) . '</label><br />' . "\n";
        echo '<input type="hidden" class="' . esc_attr( self::return_menu_icon_name() . '-attachment_id' ) . '" name="' . esc_attr( self::return_menu_icon_name() . '-attachment_id[' . $item_id . ']' ) . '" id="' . esc_attr( $prefix . '-attachment_id' ) . '" value="' . esc_attr( $attachment_id ? $attachment_id : '' ) . '" />' . "\n";
        echo '<input type="url" class="' . esc_attr( self::return_menu_icon_name() . '-attachment_url' ) . '" name="' . esc_attr( self::return_menu_icon_name() . '-attachment_url[' . $item_id . ']' ) . '" id="' . esc_attr( $prefix . '-attachment_url' ) . '" value="' . esc_url( $attachment_id ? wp_get_attachment_url( $attachment_id ) : '' ) . '" size="50" />' . "\n";
        echo '<input type="button" class="button ' . esc_attr( self::return_menu_icon_name() . '-upload_button' ) . '" id="' . esc_attr( $prefix . '-upload_button' ) . '" value="' . esc_attr__( 'Upload/Select Image', self::return_plugin_text_domain() ) . '" />' . "\n";
        echo '</p>' . "\n";

    }

    public function add_menu_icon_meta( $menu_id, $menu_item_db_id, $menu_item_data = array() ) {

        $nonce_name = self::return_menu_icon_name() . '-' . $menu_item_db_id . '-nonce';

        // Verify this came from our screen and with proper authorization.
        if ( ! isset( $_POST[ $nonce_name ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ $nonce_name ] ) ), self::return_menu_icon_name() . '-nonce' ) ) {

            return;

        }

        if ( ! current_user_can( 'edit_theme_options' ) ) {

            return;

        }

        $field         = self::return_menu_icon_name() . '-attachment_id';
        $attachment_id = isset( $_POST[ $field ][ $menu_item_db_id ] ) ? absint( wp_unslash( $_POST[ $field ][ $menu_item_db_id ] ) ) : 0;

        if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {

            update_post_meta( $menu_item_db_id, '_' . self::return_menu_icon_name() . '-attachment_id', $attachment_id );

        } else {

            delete_post_meta( $menu_item_db_id, '_' . self::return_menu_icon_name() . '-attachment_id' );

        }

    }

    public static function get_instance(){

        if ( null === self::$instance ) {

            self::$instance = new self();

        }

        return self::$instance;

    }

    public function __construct() {

        add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'add_menu_icon_uploader' ), 10, 5 );
        add_action( 'wp_update_nav_menu_item', array( $this, 'add_menu_icon_meta' ), 10, 3 );

    }

}

LH_web_application_menu_icon_class::get_instance();
