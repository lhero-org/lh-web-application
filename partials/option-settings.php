<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Included by LH_web_application_settings_class::plugin_options(), which provides $options.

$lh_web_application_mode = ! empty( $options[ self::return_display_mode_field_name() ] ) ? $options[ self::return_display_mode_field_name() ] : '';

$lh_web_application_image_fields = array(
    array( self::return_manifest_icon_name(), __( 'Manifest Icon:', self::return_plugin_text_domain() ), self::return_manifest_icon_id() ),
    array( self::return_ios_icon_name(), __( 'iOS Icon:', self::return_plugin_text_domain() ), self::return_ios_icon_id() ),
    array( self::return_ios_startup_name(), __( 'iOS Startup Image:', self::return_plugin_text_domain() ), self::return_ios_startup_id() ),
);

echo '<div class="wrap">' . "\n";
echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>' . "\n";

echo '<p>';
echo esc_html__( 'Your manifest is: ', self::return_plugin_text_domain() );
echo '<a href="' . esc_url( self::return_manifest_url() ) . '">' . esc_html( self::return_manifest_url() ) . '</a>';
echo '<br/>';
echo esc_html__( 'Your Service worker is: ', self::return_plugin_text_domain() );
echo '<a href="' . esc_url( home_url( '/' . self::return_sw_file_name() ) ) . '">' . esc_html( home_url( '/' . self::return_sw_file_name() ) ) . '</a>';
echo '</p>' . "\n";

echo '<form name="form1" method="post" action="">' . "\n";

wp_nonce_field( self::return_plugin_namespace() . '-nonce', self::return_plugin_namespace() . '-nonce', false );

// admin-uploader.js fills these when an image is picked with the matching button.
echo '<input type="hidden" name="' . esc_attr( self::return_manifest_icon_attachment_id_name() ) . '" id="' . esc_attr( self::return_manifest_icon_attachment_id_name() ) . '" value="' . esc_attr( self::return_manifest_icon_id() ) . '" />' . "\n";
echo '<input type="hidden" name="' . esc_attr( self::return_ios_icon_attachment_id_name() ) . '" id="' . esc_attr( self::return_ios_icon_attachment_id_name() ) . '" value="' . esc_attr( self::return_ios_icon_id() ) . '" />' . "\n";
echo '<input type="hidden" name="' . esc_attr( self::return_ios_startup_attachment_id_name() ) . '" id="' . esc_attr( self::return_ios_startup_attachment_id_name() ) . '" value="' . esc_attr( self::return_ios_startup_id() ) . '" />' . "\n";

echo '<table class="form-table" role="presentation">' . "\n";

// Short name.
echo '<tr>' . "\n";
echo '<th scope="row"><label for="' . esc_attr( self::return_short_name_field_name() ) . '">' . esc_html__( 'A short name for your Web App: ', self::return_plugin_text_domain() ) . '</label></th>' . "\n";
echo '<td><input type="text" name="' . esc_attr( self::return_short_name_field_name() ) . '" id="' . esc_attr( self::return_short_name_field_name() ) . '" value="' . esc_attr( ! empty( $options[ self::return_short_name_field_name() ] ) ? $options[ self::return_short_name_field_name() ] : '' ) . '" /></td>' . "\n";
echo '</tr>' . "\n";

// Images, each with an Upload/Select Image button.
foreach ( $lh_web_application_image_fields as $lh_web_application_index => $lh_web_application_image ) {

    list( $lh_web_application_prefix, $lh_web_application_label, $lh_web_application_id ) = $lh_web_application_image;

    echo '<tr>' . "\n";
    echo '<th scope="row"><label for="' . esc_attr( $lh_web_application_prefix . '-attachment_url' ) . '">' . esc_html( $lh_web_application_label ) . '</label></th>' . "\n";
    echo '<td><input type="url" name="' . esc_attr( $lh_web_application_prefix . '-attachment_url' ) . '" id="' . esc_attr( $lh_web_application_prefix . '-attachment_url' ) . '" value="' . esc_url( $lh_web_application_id ? wp_get_attachment_url( $lh_web_application_id ) : '' ) . '" size="50" /> ';
    echo '<input type="button" class="button" id="' . esc_attr( $lh_web_application_prefix . '-upload_button' ) . '" value="' . esc_attr__( 'Upload/Select Image', self::return_plugin_text_domain() ) . '" /></td>' . "\n";
    echo '</tr>' . "\n";

    // The maskable choice belongs to the manifest icon, so it follows it.
    if ( 0 === $lh_web_application_index ) {

        echo '<tr>' . "\n";
        echo '<th scope="row"><label for="' . esc_attr( self::return_is_maskable_icon_name() ) . '">' . esc_html__( 'Is Maskable:', self::return_plugin_text_domain() ) . '</label></th>' . "\n";
        echo '<td><select name="' . esc_attr( self::return_is_maskable_icon_name() ) . '" id="' . esc_attr( self::return_is_maskable_icon_name() ) . '">' . "\n";
        echo '<option value="0"' . selected( 1 != self::return_is_maskable_icon_state(), true, false ) . '>' . esc_html__( 'No', self::return_plugin_text_domain() ) . '</option>' . "\n";
        echo '<option value="1"' . selected( 1 == self::return_is_maskable_icon_state(), true, false ) . '>' . esc_html__( 'Yes', self::return_plugin_text_domain() ) . '</option>' . "\n";
        echo '</select></td>' . "\n";
        echo '</tr>' . "\n";

    }

}

// Display mode.
echo '<tr>' . "\n";
echo '<th scope="row"><label for="' . esc_attr( self::return_display_mode_field_name() ) . '">' . esc_html__( 'Display mode:', self::return_plugin_text_domain() ) . '</label></th>' . "\n";
echo '<td><select name="' . esc_attr( self::return_display_mode_field_name() ) . '" id="' . esc_attr( self::return_display_mode_field_name() ) . '">' . "\n";

foreach ( array(
    'browser'    => __( 'Browser', self::return_plugin_text_domain() ),
    'fullscreen' => __( 'Fullscreen', self::return_plugin_text_domain() ),
    'standalone' => __( 'Standalone', self::return_plugin_text_domain() ),
    'minimal-ui' => __( 'Minimal-ui', self::return_plugin_text_domain() ),
) as $lh_web_application_value => $lh_web_application_label ) {

    echo '<option value="' . esc_attr( $lh_web_application_value ) . '"' . selected( $lh_web_application_mode, $lh_web_application_value, false ) . '>' . esc_html( $lh_web_application_label ) . '</option>' . "\n";

}

echo '</select></td>' . "\n";
echo '</tr>' . "\n";

// Apple web app capable.
$lh_web_application_capable = isset( $options[ self::return_apple_web_app_capable_field_name() ] ) ? (string) $options[ self::return_apple_web_app_capable_field_name() ] : '';

echo '<tr>' . "\n";
echo '<th scope="row"><label for="' . esc_attr( self::return_apple_web_app_capable_field_name() ) . '">' . esc_html__( 'Apple Web App Capable:', self::return_plugin_text_domain() ) . '</label></th>' . "\n";
echo '<td><select name="' . esc_attr( self::return_apple_web_app_capable_field_name() ) . '" id="' . esc_attr( self::return_apple_web_app_capable_field_name() ) . '">' . "\n";
echo '<option value="1"' . selected( $lh_web_application_capable, '1', false ) . '>' . esc_html__( 'Yes', self::return_plugin_text_domain() ) . '</option>' . "\n";
echo '<option value="0"' . selected( $lh_web_application_capable, '0', false ) . '>' . esc_html__( 'No', self::return_plugin_text_domain() ) . '</option>' . "\n";
echo '</select> - ' . esc_html__( 'Set this to Yes to let the site run as a web app on Apple devices.', self::return_plugin_text_domain() ) . '</td>' . "\n";
echo '</tr>' . "\n";

// Shortcut menu.
$lh_web_application_shortcut = ! empty( $options[ self::return_shortcut_menu_name() ] ) ? $options[ self::return_shortcut_menu_name() ] : '0';

echo '<tr>' . "\n";
echo '<th scope="row"><label for="' . esc_attr( self::return_shortcut_menu_name() ) . '">' . esc_html__( 'Shortcut Menu:', self::return_plugin_text_domain() ) . '</label></th>' . "\n";
echo '<td><select name="' . esc_attr( self::return_shortcut_menu_name() ) . '" id="' . esc_attr( self::return_shortcut_menu_name() ) . '">' . "\n";
echo '<option value="0"' . selected( $lh_web_application_shortcut, '0', false ) . '>' . esc_html__( 'None', self::return_plugin_text_domain() ) . '</option>' . "\n";

foreach ( wp_get_nav_menus() as $lh_web_application_menu ) {

    echo '<option value="' . esc_attr( $lh_web_application_menu->slug ) . '"' . selected( $lh_web_application_shortcut, $lh_web_application_menu->slug, false ) . '>' . esc_html( $lh_web_application_menu->name ) . '</option>' . "\n";

}

echo '</select></td>' . "\n";
echo '</tr>' . "\n";

// Colours.
echo '<tr>' . "\n";
echo '<th scope="row"><label for="' . esc_attr( self::return_theme_color_field_name() ) . '">' . esc_html__( 'Theme Colour:', self::return_plugin_text_domain() ) . '</label></th>' . "\n";
echo '<td><input type="text" name="' . esc_attr( self::return_theme_color_field_name() ) . '" id="' . esc_attr( self::return_theme_color_field_name() ) . '" value="' . esc_attr( ! empty( $options[ self::return_theme_color_field_name() ] ) ? $options[ self::return_theme_color_field_name() ] : '' ) . '" class="color-picker" size="10" /></td>' . "\n";
echo '</tr>' . "\n";

echo '<tr>' . "\n";
echo '<th scope="row"><label for="' . esc_attr( self::return_background_color_field_name() ) . '">' . esc_html__( 'Background Color:', self::return_plugin_text_domain() ) . '</label></th>' . "\n";
echo '<td><input type="text" name="' . esc_attr( self::return_background_color_field_name() ) . '" id="' . esc_attr( self::return_background_color_field_name() ) . '" value="' . esc_attr( (string) self::return_background_colour() ) . '" class="color-picker" size="10" /></td>' . "\n";
echo '</tr>' . "\n";

// Install prompt.
$lh_web_application_install_prompt = self::return_install_prompt_audience();

echo '<tr>' . "\n";
echo '<th scope="row"><label for="' . esc_attr( self::return_install_prompt_field_name() ) . '">' . esc_html__( 'Install prompt:', self::return_plugin_text_domain() ) . '</label></th>' . "\n";
echo '<td><select name="' . esc_attr( self::return_install_prompt_field_name() ) . '" id="' . esc_attr( self::return_install_prompt_field_name() ) . '">' . "\n";

foreach ( array(
    'members'  => __( 'Signed-in members of this site', self::return_plugin_text_domain() ),
    'everyone' => __( 'Everyone', self::return_plugin_text_domain() ),
    'none'     => __( 'No one', self::return_plugin_text_domain() ),
) as $lh_web_application_value => $lh_web_application_label ) {

    echo '<option value="' . esc_attr( $lh_web_application_value ) . '"' . selected( $lh_web_application_install_prompt, $lh_web_application_value, false ) . '>' . esc_html( $lh_web_application_label ) . '</option>' . "\n";

}

echo '</select>';
echo '<p class="description">' . esc_html__( 'Who is offered the app on their phone, once, from their third visit. Anyone can still open it from the Install App menu item.', self::return_plugin_text_domain() ) . '</p></td>' . "\n";
echo '</tr>' . "\n";

// Why install.
echo '<tr>' . "\n";
echo '<th scope="row"><label for="' . esc_attr( self::return_install_reason_field_name() ) . '">' . esc_html__( 'Why install:', self::return_plugin_text_domain() ) . '</label></th>' . "\n";
echo '<td><input type="text" class="large-text" name="' . esc_attr( self::return_install_reason_field_name() ) . '" id="' . esc_attr( self::return_install_reason_field_name() ) . '" value="' . esc_attr( ! empty( $options[ self::return_install_reason_field_name() ] ) ? $options[ self::return_install_reason_field_name() ] : '' ) . '" placeholder="' . esc_attr( self::return_install_reason() ) . '" />';
echo '<p class="description">' . esc_html__( 'One line in the install sheet saying what the app gives people. Leave empty for the default.', self::return_plugin_text_domain() ) . '</p></td>' . "\n";
echo '</tr>' . "\n";

// Offline message.
echo '<tr>' . "\n";
echo '<th scope="row"><label for="' . esc_attr( self::return_offline_message_field_name() ) . '">' . esc_html__( 'Offline Message:', self::return_plugin_text_domain() ) . '</label></th>' . "\n";
echo '<td>';
wp_editor( (string) self::get_offline_message(), self::return_offline_message_field_name(), array( 'media_buttons' => false ) );
echo '</td>' . "\n";
echo '</tr>' . "\n";

echo '</table>' . "\n";

submit_button();

echo '</form>' . "\n";
echo '</div>' . "\n";
