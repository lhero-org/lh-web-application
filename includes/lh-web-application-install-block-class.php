<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The Install the App block (lh-web-application/install-button): a button that sites place where they
 * want to ask people to install the app, such as a members' page or a registration confirmation.
 * It carries a data-lh-web-application-install attribute, so the install script opens the install
 * sheet from it and reports the block's trigger name as detail.trigger. The block's stylesheet hides
 * it inside the installed app and, when Hide once installed is on, in a browser that has installed it.
 */
class LH_web_application_install_block_class extends LH_web_application_plugin {

    private static $instance;

    static function return_block_name(){

        return self::return_plugin_text_domain() . '/install-button';

    }

    static function return_block_dir(){

        return dirname( __DIR__ ) . '/blocks/install-button';

    }

    // Reported as detail.trigger when the block has no trigger name of its own.
    static function return_default_trigger(){

        return 'block';

    }

    // Lower case letters, numbers, dashes and underscores, as analytics will see it.
    static function sanitize_trigger( $value ){

        $value = sanitize_key( (string) $value );

        return '' === $value ? self::return_default_trigger() : $value;

    }

    public function register_blocks(){

        register_block_type( self::return_block_dir(), array(
            'render_callback' => array( $this, 'render_install_button' ),
        ) );

    }

    public function render_install_button( $attributes, $content = '', $block = null ){

        $text_domain = self::return_plugin_text_domain();

        // The editor stores the label as rich text with no formats, so only entities need undoing.
        $text = isset( $attributes['text'] ) ? trim( html_entity_decode( wp_strip_all_tags( (string) $attributes['text'] ), ENT_QUOTES, 'UTF-8' ) ) : '';

        if ( '' === $text ) {

            $text = __( 'Install the app', $text_domain );

        }

        $trigger = self::sanitize_trigger( isset( $attributes['trigger'] ) ? $attributes['trigger'] : '' );
        $classes = array();

        if ( ! empty( $attributes['textAlign'] ) && in_array( $attributes['textAlign'], array( 'left', 'center', 'right' ), true ) ) {

            $classes[] = 'has-text-align-' . $attributes['textAlign'];

        }

        if ( ! isset( $attributes['hideWhenInstalled'] ) || ! empty( $attributes['hideWhenInstalled'] ) ) {

            $classes[] = 'is-hidden-when-installed';

        }

        $wrapper = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) );

        return sprintf(
            '<div %1$s><button type="button" class="wp-block-lh-web-application-install-button__link wp-element-button" data-lh-web-application-install="%2$s">%3$s</button></div>',
            $wrapper,
            esc_attr( $trigger ),
            esc_html( $text )
        );

    }

    public static function get_instance(){

        if ( null === self::$instance ) {

            self::$instance = new self();

        }

        return self::$instance;

    }

    public function __construct() {

        //register the block
        add_action( 'init', array( $this, 'register_blocks' ) );

    }

}

LH_web_application_install_block_class::get_instance();
