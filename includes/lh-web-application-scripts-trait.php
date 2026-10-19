<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Script helpers shared by the classes that register this plugin's scripts.
 * Used by LH_web_application_plugin, so the attributes are shared by every child class.
 */
trait LH_web_application_scripts_trait {

    /**
     * Extra attributes for this plugin's script tags, keyed by script handle.
     * Shared with the child classes, and added by filter_script_loader_tag().
     */
    protected static $script_attributes = array();

    static function add_script_attributes( $handle, $attributes ){

        self::$script_attributes[ $handle ] = $attributes;

    }

    public function filter_script_loader_tag( $tag, $handle, $src ){

        if ( empty( self::$script_attributes[ $handle ] ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {

            return $tag;

        }

        $processor = new WP_HTML_Tag_Processor( $tag );

        // The tag can include inline before and after scripts; the attributes belong on the one with a src.
        while ( $processor->next_tag( array( 'tag_name' => 'script' ) ) ) {

            if ( null === $processor->get_attribute( 'src' ) ) {

                continue;

            }

            foreach ( self::$script_attributes[ $handle ] as $name => $value ) {

                $processor->set_attribute( $name, $value );

            }

            break;

        }

        return $processor->get_updated_html();

    }

    static function get_script_src_by_handle($handle) {
        
        global $wp_scripts;
        
        if(!empty($wp_scripts->registered[$handle]->src)) {
            
            return add_query_arg('ver', $wp_scripts->registered[$handle]->ver, $wp_scripts->registered[$handle]->src);
        
        } else {
            
            return false;
            
        }
        
    }

}
