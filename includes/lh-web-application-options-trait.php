<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Option, field and endpoint names, and the readers for the saved settings.
 * Used by LH_web_application_plugin, so every method stays callable on it and its child classes.
 */
trait LH_web_application_options_trait {

    // URL slug of the fake page

    static function return_offline_page_slug(){

        return 'offline';

    }

    static function return_offline_page_url(){

        return trailingslashit(home_url() . '/' . self::return_offline_page_slug());

    }



    static function return_opt_name(){

        return self::return_plugin_namespace().'-options';

    }

    static function return_manifest_icon_attachment_id_name(){

        return 'lh_web_application-manifest_icon_attachment_id';

    }

    static function return_ios_icon_attachment_id_name(){

        return 'lh_web_application-ios_icon_attachment_id';

    }

    static function return_short_name_field_name(){

        return 'lh_web_application-short_name_field_name';

    }

    static function return_background_color_field_name(){

        return 'lh_web_application-background_color_field_name';

    }

    static function return_apple_web_app_capable_field_name(){

        return 'lh_web_application-apple_web_app_capable';

    }

    static function return_theme_color_field_name(){

        return 'lh_web_application-theme_color_field_name';

    }

    static function return_display_mode_field_name(){

        return 'lh_web_application-display_mode_field_name';

    }

    static function return_offline_message_field_name(){

        return 'lh_web_application-offline_message_field_name';

    }

    static function return_manifest_icon_name(){

        return 'lh_web_application-manifest_icon';

    }

    static function return_ios_icon_name(){

        return 'lh_web_application-ios_icon';

    }

    static function return_ios_startup_name(){

        return 'lh_web_application-ios_startup';

    }

    static function return_shortcut_menu_name(){

        return 'lh_web_application-shortcut_menu';

    }

    static function return_is_maskable_icon_name(){

        return 'lh_web_application-is_maskable_icon';

    }

    static function return_menu_icon_name(){

        return 'lh_web_application-menu_icon';

    }

    static function return_ios_startup_attachment_id_name(){

        return 'lh_web_application-ios_startup_attachment_id';

    }

    static function return_sw_file_name(){

        return 'serviceworker.script';

    }

    static function return_manifest_file_name(){

        return 'pwa-manifest.json';

    }

    static function return_manifest_url(){

        return home_url('/'.self::return_manifest_file_name());

    }

    static function return_start_url(){

        return add_query_arg( self::return_plugin_namespace().'-start_url', '1', home_url( '/' ) );

    }

    static function return_start_mode(){

        $options = get_option(self::return_opt_name());

        if (!empty($options[self::return_display_mode_field_name()])){

            return $options[self::return_display_mode_field_name()];

        } else {

            return 'browser';

        }

    }

    static function return_manifest_icon_id(){

        $options = get_option(self::return_opt_name());

        if (!empty($options[self::return_manifest_icon_attachment_id_name()])){

            return $options[self::return_manifest_icon_attachment_id_name()];

        } elseif (!empty(get_option('site_icon'))){

            return get_option('site_icon');

        } else {

            return false;

        }

    }

    protected function return_is_maskable_icon_state(){

        $options = get_option(self::return_opt_name());

        if (!empty($options[self::return_is_maskable_icon_name()])){

            return $options[self::return_is_maskable_icon_name()];

        } else {

            return false;

        }

    }

    static function return_ios_icon_id(){

        $options = get_option(self::return_opt_name());

        if (!empty($options[self::return_ios_icon_attachment_id_name()])){

            return $options[self::return_ios_icon_attachment_id_name()];

        } elseif (!empty(get_option('site_icon'))){

            return get_option('site_icon');

        } else {

            return false;

        }

    }

    static function return_ios_startup_id(){

        $options = get_option(self::return_opt_name());

        if (!empty($options[self::return_ios_startup_attachment_id_name()])){

            $return = $options[self::return_ios_startup_attachment_id_name()];

        } else {

            $return = false;
        }

        return apply_filters(self::return_plugin_namespace().'_return_ios_startup_id', $return);

    }

    static function return_background_colour(){

        $options = get_option(self::return_opt_name());

        if (!empty($options[ self::return_background_color_field_name() ])){

            return $options[ self::return_background_color_field_name() ];

        } elseif (!empty(get_theme_mod( 'background_color' ))) {

            // The theme stores its background colour without the leading #.
            return '#' . ltrim( get_theme_mod( 'background_color' ), '#' );

        } else {

            return false;

        }

    }

    /**
     * @deprecated 1.34.0 Use return_background_colour().
     */
    static function get_webb_app_background_colour(){

        _deprecated_function( 'LH_web_application_plugin::get_webb_app_background_colour', '1.34.0', 'LH_web_application_plugin::return_background_colour' );

        return self::return_background_colour();

    }

    static function get_offline_message(){

        $options = get_option(self::return_opt_name());

        if (!empty($options[ self::return_offline_message_field_name() ]) && !empty(trim($options[ self::return_offline_message_field_name() ]))){

            return $options[ self::return_offline_message_field_name() ];

        } else {

            return false;

        }

    }

    static function return_offline_html(){

        if (self::get_offline_message()){

            return self::get_offline_message();

        }

        return '';

    }

    static function return_install_prompt_field_name(){

        return 'lh_web_application-install_prompt';

    }

    static function return_install_reason_field_name(){

        return 'lh_web_application-install_reason';

    }

    // Who is offered the install sheet on their own, without asking for it.
    static function return_install_prompt_audiences(){

        return array( 'members', 'everyone', 'none' );

    }

    static function return_install_prompt_audience(){

        $options  = get_option( self::return_opt_name() );
        $audience = ( is_array( $options ) && ! empty( $options[ self::return_install_prompt_field_name() ] ) ) ? $options[ self::return_install_prompt_field_name() ] : 'members';
        $audience = apply_filters( self::return_plugin_namespace() . '_install_prompt_audience', $audience );

        return in_array( $audience, self::return_install_prompt_audiences(), true ) ? $audience : 'members';

    }

    // The line under the sheet's heading saying why the app is worth installing.
    static function return_install_reason(){

        $options = get_option( self::return_opt_name() );
        $reason  = ( is_array( $options ) && ! empty( $options[ self::return_install_reason_field_name() ] ) ) ? $options[ self::return_install_reason_field_name() ] : __( 'Open it straight from your home screen, full screen and quicker to load.', self::return_plugin_text_domain() );

        return apply_filters( self::return_plugin_namespace() . '_install_reason', $reason );

    }

}
