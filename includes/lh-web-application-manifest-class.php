<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The web app manifest: built from the site's settings and served at /pwa-manifest.json.
 */
class LH_web_application_manifest_class extends LH_web_application_plugin {

    private static $instance;

    static function return_shortcut_menu_array(){

        $options = get_option(self::return_opt_name());

        if (empty($options[self::return_shortcut_menu_name()])){

            return false;

        }

        $the_menu = wp_get_nav_menu_object($options[self::return_shortcut_menu_name()]);

        // The chosen menu may have been deleted since it was saved.
        if (empty($the_menu->term_id)){

            return false;

        }

        $menu_items = wp_get_nav_menu_items($the_menu->term_id);

        if (empty($menu_items)){

            return false;

        }

        $menu_array = array();

        foreach( $menu_items as $menu_item ){

            $menu_array[] = wp_setup_nav_menu_item($menu_item);

        }

        $shortcut_array = array();

        foreach( $menu_array as $menu_member ){

            $object_id = (int) $menu_member->object_id; //object ID.

            $add['name'] = $menu_member->title;
            $add['short_name'] = $menu_member->title;
            $add['description'] = $menu_member->description;
            $add['url'] = $menu_member->url;

            $attachment_id = get_post_meta( $menu_member->ID, '_'.self::return_menu_icon_name().'-attachment_id', true );

            if (!empty($attachment_id)){

                $mime = get_post_mime_type( $attachment_id );
                $full_url = wp_get_attachment_url($attachment_id);

                foreach( self::create_manifest_icon_sizes() as $size ){

                    $icon['src'] = self::maybe_return_new_image_url($full_url, array($size['width'], $size['height']));
                    $icon['sizes']= $size['width'].'x'.$size['height'];
                    $icon['type'] = $mime;

                    $add['icons'][] = $icon;

                    unset($icon);

                }

            }

            $shortcut_array[] = $add;

            unset($add);

        }

        // There is at least one menu item here, so there is at least one shortcut.
        return $shortcut_array;

    }

    /**
     * @deprecated 1.34.0 Use return_shortcut_menu_array().
     */
    static function return_shorcut_menu_array(){

        _deprecated_function( 'LH_web_application_manifest_class::return_shorcut_menu_array', '1.34.0', 'LH_web_application_manifest_class::return_shortcut_menu_array' );

        return self::return_shortcut_menu_array();

    }

    protected function return_manifest_json_as_var(){

        // Site name and tagline are stored HTML-escaped; the manifest wants plain text.
        $json['name'] = html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' );

        $description = html_entity_decode( get_bloginfo( 'description' ), ENT_QUOTES, 'UTF-8' );

        if ( '' !== trim( $description ) ) {

            $json['description'] = $description;

        }

        $json['lang'] = get_bloginfo( 'language' );
        $json['dir'] = is_rtl() ? 'rtl' : 'ltr';
        $json['orientation'] = 'any';
        $json['scope'] = '/';


        $related_applications = array();
        $add_application['platform'] = 'webapp';
        $add_application['url'] = self::return_manifest_url();
        $related_applications[] = $add_application;


        $json['related_applications'] = $related_applications;

        $json['display'] = self::return_start_mode();

        $options = get_option(self::return_opt_name());


        if(!empty($options[self::return_short_name_field_name()])){ $json['short_name'] = $options[self::return_short_name_field_name()]; }

        $json['start_url'] = self::return_start_url();

        // Browsers identify an installed app by its id: never change how this is built, or existing installs are orphaned.
        $json['id'] = wp_hash(self::return_plugin_namespace().'_'.get_current_blog_id());

        if (!empty($options[ self::return_theme_color_field_name()])){

            $json['theme_color'] = $options[ self::return_theme_color_field_name() ];

        }

        if (!empty(self::return_background_colour())){

            $json['background_color'] = self::return_background_colour();

        }

        $mime = get_post_mime_type( self::return_manifest_icon_id() );

        $i = 0;

        foreach( self::create_manifest_icon_sizes() as $size ){

            if ($href = self::check_image_size(self::return_manifest_icon_id(),self::return_manifest_icon_name(), $size['width'], $size['height'])){

                $json['icons'][$i]['src'] = $href;
                $json['icons'][$i]['sizes']= $size['width'].'x'.$size['height'];
                $json['icons'][$i]['type'] = $mime;

                if (!empty($options[self::return_is_maskable_icon_name()]) && ($options[self::return_is_maskable_icon_name()] == 1)){

                    $json['icons'][$i]['purpose'] = "any maskable";

                }

               $i++;

            }

        }

        if (self::return_shortcut_menu_array()){

            $json['shortcuts'] = self::return_shortcut_menu_array();

        }

        return apply_filters( 'lh_web_application_manifest_json_filter', $json);

    }

    public function add_query_vars( $vars ){

        $vars[] = '__lh_web_application-manifest';

        return $vars;

    }

    public function add_endpoint(){

        add_rewrite_rule('pwa-manifest\.json$','index.php?__lh_web_application-manifest=1','top');

    }

    public function cache_request_json( $eof_pattern){

        global $wp_super_cache_comments;

        // Accept a JSON-formatted string as an end-of-file marker, so that the page will be cached
        $json_object_pattern     = '^[{].*[}]$';
        $json_collection_pattern = '^[\[].*[\]]$';

        $eof_pattern = str_replace(
            '<\?xml',
            sprintf( '<\?xml|%s|%s', $json_object_pattern, $json_collection_pattern ),
            $eof_pattern
        );

        // Don't append HTML comments to the JSON output, because that would invalidate it
        $wp_super_cache_comments = false;

        return $eof_pattern;

    }

    public function maybe_serve_manifest(){

        global $wp;
        global $wp_super_cache_comments;

        if ( isset( $wp->query_vars['__lh_web_application-manifest'] ) ) {

            $wp_super_cache_comments = false;
            add_filter( 'wp_cache_eof_tags', array( $this, 'cache_request_json' ) );

            header( 'Content-type: application/manifest+json' );
            header( 'Access-Control-Allow-Origin: *' );
            echo wp_json_encode( $this->return_manifest_json_as_var() );
            do_action( 'shutdown' );

            exit;

        }

    }

    public static function get_instance(){

        if ( null === self::$instance ) {

            self::$instance = new self();

        }

        return self::$instance;

    }

    public function __construct() {

        add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
        add_action( 'init', array( $this, 'add_endpoint' ), 0 );
        add_action( 'wp', array( $this, 'maybe_serve_manifest' ), PHP_INT_MAX );

    }

}

LH_web_application_manifest_class::get_instance();
