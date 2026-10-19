<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The install sheet: a panel that slides up from the bottom of the page and explains how to
 * install the site as an app, or offers the browser's own install prompt. It is opened by the
 * Install App menu item (via LH Javascript Menu Items), by any element with a
 * data-lh-web-application-install attribute, and once by itself for visitors who keep coming
 * back. Also the snooze endpoint and the [lh_web_application_has_prompt] shortcode.
 */
class LH_web_application_app_install_class extends LH_web_application_plugin {

    private static $instance;

    static function return_class_namespace(){

        return 'app_install';

    }

    // LH Javascript Menu Items enqueues this handle for the Install App menu item.
    static function return_trigger_handle(){

        return self::return_plugin_namespace() . '_trigger_' . self::return_class_namespace();

    }

    static function return_sheet_id(){

        return self::return_plugin_namespace() . '-install';

    }

    static function return_snooze_action(){

        return self::return_plugin_namespace() . '_install_snooze';

    }

    // A user option, so the snooze is per site on a network.
    static function return_snooze_option_name(){

        return self::return_plugin_namespace() . '-install_snoozed_until';

    }

    // Visits (browsing sessions) before the sheet is offered by itself.
    static function return_auto_visits(){

        return max( 1, absint( apply_filters( self::return_plugin_namespace() . '_install_prompt_visits', 3 ) ) );

    }

    // Days the sheet stays away once it has been closed.
    static function return_snooze_days(){

        return max( 1, absint( apply_filters( self::return_plugin_namespace() . '_install_prompt_snooze_days', 30 ) ) );

    }

    static function return_snoozed_until( $user_id ){

        return absint( get_user_option( self::return_snooze_option_name(), $user_id ) );

    }

    // Whether this visitor may be offered the sheet without asking for it.
    static function is_auto_prompt_allowed(){

        $audience = self::return_install_prompt_audience();

        if ( 'everyone' === $audience ) {

            return true;

        }

        if ( 'members' === $audience ) {

            return is_user_logged_in() && is_user_member_of_blog();

        }

        return false;

    }

    static function return_app_name(){

        $options = get_option( self::return_opt_name() );

        if ( is_array( $options ) && ! empty( $options[ self::return_short_name_field_name() ] ) ) {

            return $options[ self::return_short_name_field_name() ];

        }

        return get_bloginfo( 'name' );

    }

    static function return_icon_url(){

        $icon_id = self::return_ios_icon_id();
        $url     = $icon_id ? wp_get_attachment_image_url( $icon_id, array( 112, 112 ) ) : false;

        return $url ? $url : get_site_icon_url( 112 );

    }

    public function register_scripts_and_styles() {

        if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || defined( 'XMLRPC_REQUEST' ) || wp_doing_cron() || is_robots() ) {

            return;

        }

        $trigger_path = 'assets/scripts/app_install-boot.js';

        wp_register_script( self::return_trigger_handle(), self::return_asset_url( $trigger_path ), array(), self::return_asset_version( $trigger_path ), array( 'in_footer' => true, 'strategy' => 'defer' ) );

        $user_id = get_current_user_id();

        // The script finds itself by this id. The snooze fields are only for signed-in visitors.
        self::add_script_attributes( self::return_trigger_handle(), array(
            'id'                          => self::return_trigger_handle(),
            'data-install-auto'           => self::is_auto_prompt_allowed() ? '1' : '0',
            'data-install-auto-visits'    => (string) self::return_auto_visits(),
            'data-install-snooze-days'    => (string) self::return_snooze_days(),
            'data-install-snoozed-until'  => $user_id ? (string) self::return_snoozed_until( $user_id ) : '0',
            'data-install-snooze-url'     => $user_id ? admin_url( 'admin-ajax.php' ) : '',
            'data-install-snooze-action'  => self::return_snooze_action(),
            'data-install-snooze-nonce'   => $user_id ? wp_create_nonce( self::return_snooze_action() ) : '',
        ) );

    }

    // On every front-end page, so the sheet can offer itself; the menu item enqueues it too.
    public function enqueue_scripts(){

        wp_enqueue_script( self::return_trigger_handle() );

    }

    public function create_dynamic_menu_item( $elems ){

        $add_array           = array();
        $add_array['title']  = __( 'Install App', self::return_plugin_text_domain() );
        $add_array['script'] = self::return_trigger_handle();
        $add_array['id']     = self::return_trigger_handle();

        $elems[ '#' . self::return_trigger_handle() . '#' ] = $add_array;

        return $elems;

    }

    public static function get_instance(){

        if ( null === self::$instance ) {

            self::$instance = new self();

        }

        return self::$instance;

    }

    public function prevent_from_lazy_load( $excluded_scripts ){

        $excluded_scripts[] = self::return_trigger_handle();

        return $excluded_scripts;

    }

    // Id selectors, so a theme's general button and heading rules cannot leak in.
    static function return_sheet_css(){

        $id = '#' . self::return_sheet_id();

        $css = $id . '{position:fixed;top:auto;right:0;bottom:0;left:0;z-index:100001;box-sizing:border-box;width:100%;max-width:512px;max-height:85vh;margin:0 auto;padding:0;border:0;border-radius:16px 16px 0 0;background:var(--lh-web-application-install-background,#fff);color:var(--lh-web-application-install-color,#222);box-shadow:0 -4px 24px rgba(0,0,0,.25);font-family:inherit;font-size:16px;line-height:1.45;overflow:auto}'
            . $id . '[open]{animation:lh_web_application-install_up .25s ease-out}'
            . '@keyframes lh_web_application-install_up{from{transform:translateY(100%)}to{transform:none}}'
            . $id . '::backdrop{background:rgba(0,0,0,.4)}'
            . $id . ' .lh_web_application-install_sheet{padding:20px 20px calc(20px + env(safe-area-inset-bottom,0px))}'
            . $id . ' .lh_web_application-install_head{display:flex;align-items:center;gap:12px;margin:0 0 14px}'
            . $id . ' .lh_web_application-install_icon{flex:none;width:56px;height:56px;margin:0;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.2)}'
            . $id . ' .lh_web_application-install_titles{flex:1;min-width:0}'
            . $id . ' .lh_web_application-install_title{margin:0;padding:0;font-family:inherit;font-size:1.15em;font-weight:700;line-height:1.25;letter-spacing:normal;text-transform:none;color:inherit}'
            . $id . ' .lh_web_application-install_host{margin:2px 0 0;font-size:.85em;opacity:.7}'
            . $id . ' .lh_web_application-install_close,' . $id . ' .lh_web_application-install_close:hover,' . $id . ' .lh_web_application-install_close:focus{flex:none;align-self:flex-start;display:flex;align-items:center;justify-content:center;box-sizing:border-box;width:36px;height:36px;margin:0;padding:0;border:0;border-radius:50%;background:rgba(0,0,0,.08);color:inherit;font:inherit;text-transform:none;letter-spacing:normal;cursor:pointer;-webkit-appearance:none;appearance:none}'
            . $id . ' .lh_web_application-install_close svg{display:block;width:18px;height:18px}'
            . $id . ' p,' . $id . ' ol{margin:0 0 12px;font-size:1em;line-height:1.45}'
            . $id . ' ol{padding:0 0 0 1.4em;list-style:decimal}'
            . $id . ' li{margin:0 0 6px;padding:0}'
            . $id . ' .lh_web_application-install_share{display:inline-block;width:20px;height:20px;vertical-align:-4px;color:var(--lh-web-application-install-accent,#0a84ff)}'
            . $id . ' .lh_web_application-install_button,' . $id . ' .lh_web_application-install_button:hover,' . $id . ' .lh_web_application-install_button:focus{display:block;box-sizing:border-box;width:100%;margin:4px 0 0;padding:12px 16px;border:0;border-radius:10px;background:var(--lh-web-application-install-accent,#0a84ff);color:var(--lh-web-application-install-accent-color,#fff);font:inherit;font-weight:700;letter-spacing:normal;text-transform:none;cursor:pointer;-webkit-appearance:none;appearance:none}'
            . $id . ' .lh_web_application-install_sheet:focus{outline:0}'
            . $id . ' :focus-visible{outline:2px solid var(--lh-web-application-install-accent,#0a84ff);outline-offset:2px}'
            . $id . ' [data-for]{display:none}'
            . $id . '[data-platform="prompt"] [data-for="prompt"],' . $id . '[data-platform="ios"] [data-for="ios"],' . $id . '[data-platform="android"] [data-for="android"],' . $id . '[data-platform="macos"] [data-for="macos"],' . $id . '[data-platform="other"] [data-for="other"]{display:block}'
            . '@media (prefers-reduced-motion:reduce){' . $id . '[open]{animation:none}}'
            . '@media print{' . $id . '{display:none}}';

        return apply_filters( self::return_plugin_namespace() . '_install_sheet_css', $css );

    }

    // Printed once per page, hidden until opened, so its wording is translatable and filterable here.
    public function output_install_sheet(){

        $text_domain = self::return_plugin_text_domain();
        $name        = self::return_app_name();
        $icon        = self::return_icon_url();
        $host        = wp_parse_url( home_url(), PHP_URL_HOST );
        $title_id    = self::return_sheet_id() . '_title';

        $share_icon = '<svg class="lh_web_application-install_share" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 15V3M7.5 7.5 12 3l4.5 4.5M8 11H5v10h14V11h-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        $strong = function( $text ) {
            return '<strong>' . esc_html( $text ) . '</strong>';
        };

        $html  = '<dialog id="' . esc_attr( self::return_sheet_id() ) . '" aria-labelledby="' . esc_attr( $title_id ) . '" data-platform="other">' . "\n";
        // The sheet itself takes the focus on opening, so the close button is not ringed straight away.
        $html .= '<div class="lh_web_application-install_sheet" tabindex="-1" autofocus>' . "\n";
        $html .= '<div class="lh_web_application-install_head">' . "\n";

        if ( $icon ) {

            $html .= '<img class="lh_web_application-install_icon" src="' . esc_url( $icon ) . '" alt="" width="56" height="56" loading="lazy" />' . "\n";

        }

        $html .= '<div class="lh_web_application-install_titles">' . "\n";
        /* translators: %s: the app's name */
        $html .= '<h2 class="lh_web_application-install_title" id="' . esc_attr( $title_id ) . '">' . esc_html( sprintf( __( 'Install %s', $text_domain ), $name ) ) . '</h2>' . "\n";
        $html .= '<p class="lh_web_application-install_host">' . esc_html( $host ) . '</p>' . "\n";
        $html .= '</div>' . "\n";
        $html .= '<button type="button" class="lh_web_application-install_close" data-install-action="close" aria-label="' . esc_attr__( 'Close', $text_domain ) . '"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg></button>' . "\n";
        $html .= '</div>' . "\n";

        $html .= '<p class="lh_web_application-install_reason">' . esc_html( self::return_install_reason() ) . '</p>' . "\n";

        // Chrome, Edge and Samsung Internet hand over their own install prompt.
        $html .= '<div data-for="prompt"><button type="button" class="lh_web_application-install_button" data-install-action="install">' . esc_html__( 'Install', $text_domain ) . '</button></div>' . "\n";

        // iPhone and iPad have no prompt, so the sheet gives the steps.
        $html .= '<ol data-for="ios">' . "\n";
        /* translators: %s: the Share icon */
        $html .= '<li>' . sprintf( esc_html__( 'Tap the Share button %s. If you cannot see it, tap ••• first.', $text_domain ), $share_icon ) . '</li>' . "\n";
        /* translators: %s: the name of the menu choice, Add to Home Screen */
        $html .= '<li>' . sprintf( esc_html__( 'Scroll down and tap %s.', $text_domain ), $strong( __( 'Add to Home Screen', $text_domain ) ) ) . '</li>' . "\n";
        /* translators: %s: the name of the button, Add */
        $html .= '<li>' . sprintf( esc_html__( 'Tap %s.', $text_domain ), $strong( __( 'Add', $text_domain ) ) ) . '</li>' . "\n";
        $html .= '</ol>' . "\n";

        // Android browsers that have not offered a prompt, such as Firefox.
        $html .= '<ol data-for="android">' . "\n";
        /* translators: %s: the browser's menu button, three dots */
        $html .= '<li>' . sprintf( esc_html__( 'Tap the browser menu %s.', $text_domain ), $strong( '⋮' ) ) . '</li>' . "\n";
        /* translators: 1: the menu choice Install app, 2: the menu choice Add to Home screen */
        $html .= '<li>' . sprintf( esc_html__( 'Tap %1$s or %2$s.', $text_domain ), $strong( __( 'Install app', $text_domain ) ), $strong( __( 'Add to Home screen', $text_domain ) ) ) . '</li>' . "\n";
        $html .= '</ol>' . "\n";

        // Safari on a Mac.
        $html .= '<ol data-for="macos">' . "\n";
        /* translators: 1: the File menu, 2: the menu choice Add to Dock */
        $html .= '<li>' . sprintf( esc_html__( 'In the menu bar, choose %1$s, then %2$s.', $text_domain ), $strong( __( 'File', $text_domain ) ), $strong( __( 'Add to Dock', $text_domain ) ) ) . '</li>' . "\n";
        $html .= '</ol>' . "\n";

        $html .= '<p data-for="other">' . esc_html__( 'Use your browser\'s menu to install this site or add it to your home screen.', $text_domain ) . '</p>' . "\n";

        $html .= '</div>' . "\n";
        $html .= '</dialog>' . "\n";

        $html = apply_filters( self::return_plugin_namespace() . '_install_sheet_html', $html );

        if ( empty( $html ) ) {

            return;

        }

        // Printed with the late styles in the footer, alongside the sheet.
        wp_register_style( self::return_sheet_id(), false, array(), self::plugin_version() );
        wp_add_inline_style( self::return_sheet_id(), wp_strip_all_tags( self::return_sheet_css() ) );
        wp_enqueue_style( self::return_sheet_id() );

        echo wp_kses( $html, self::return_sheet_allowed_html() );

    }

    static function return_sheet_allowed_html(){

        $svg = array(
            'svg'  => array( 'class' => true, 'viewbox' => true, 'aria-hidden' => true, 'focusable' => true, 'width' => true, 'height' => true ),
            'path' => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ),
        );

        return array_merge( wp_kses_allowed_html( 'post' ), $svg, array(
            'dialog' => array( 'id' => true, 'class' => true, 'aria-labelledby' => true, 'data-platform' => true ),
            'div'    => array( 'class' => true, 'data-for' => true, 'tabindex' => true, 'autofocus' => true ),
            'ol'     => array( 'class' => true, 'data-for' => true ),
            'p'      => array( 'class' => true, 'data-for' => true ),
            'button' => array( 'type' => true, 'class' => true, 'data-install-action' => true, 'aria-label' => true ),
            'h2'     => array( 'class' => true, 'id' => true ),
            'img'    => array( 'class' => true, 'src' => true, 'alt' => true, 'width' => true, 'height' => true, 'loading' => true ),
        ) );

    }

    // Remembers, for the signed-in account on this site, that the sheet was closed or the app installed.
    public function handle_snooze(){

        check_ajax_referer( self::return_snooze_action(), 'nonce' );

        $user_id = get_current_user_id();

        if ( ! $user_id ) {

            wp_send_json_error( null, 403 );

        }

        $installed = isset( $_POST['installed'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['installed'] ) );
        $days      = $installed ? 5 * 365 : self::return_snooze_days();

        update_user_option( $user_id, self::return_snooze_option_name(), time() + $days * DAY_IN_SECONDS );

        wp_send_json_success();

    }

    // Content shown only where the app can be installed (the script adds the body class).
    public function has_prompt_output($atts, $content = "") {

        if (!empty($content)){

            $return = '<style>'."\n\n";
            $return .= 'div.lh_web_application-prompt_div {'."\n";
            $return .= 'display: none;'."\n";
            $return .= '}'."\n";
            $return .= '.lh_web_application-prompt_stashed div.lh_web_application-prompt_div {'."\n";
            $return .= 'display: block;'."\n";
            $return .= '}'."\n";
            $return .= '</style>'."\n";
            $return .= '<div class="lh_web_application-prompt_div">'.$content.'</div>'."\n";

            return $return;

        } else {

            return '';

        }

    }

    public function register_shortcodes(){

        add_shortcode('lh_web_application_has_prompt', array($this,'has_prompt_output'));

    }


    public function __construct() {

        //register the scripts
        add_action( 'wp_loaded', array( $this, 'register_scripts_and_styles' ), 10 );

        //load the install script on every front-end page
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        //create a dynamic menu item
        add_filter( 'lh_jmis_return_basic_menu_array', array( $this, 'create_dynamic_menu_item' ), 10, 1 );

        //prevent the trigger script from being lazy loaded
        add_filter( 'lh_defer_javascript_exclude_scripts', array( $this, 'prevent_from_lazy_load' ), 10, 1 );

        //print the install sheet in the footer of the site
        add_action( 'wp_footer', array( $this, 'output_install_sheet' ) );

        //remember a closed sheet or an installed app for the account
        add_action( 'wp_ajax_' . self::return_snooze_action(), array( $this, 'handle_snooze' ) );

        //register the shortcode
        add_action( 'init', array( $this, 'register_shortcodes' ) );

    }

}

LH_web_application_app_install_class::get_instance();
