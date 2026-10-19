(function() {

    /**
     * The install sheet: a panel that slides up from the bottom and explains how to install the
     * site as an app, or offers the browser's own install prompt where there is one. Opened by the
     * Install App menu item, by any element with a data-lh-web-application-install attribute, and
     * once on its own for visitors who keep coming back (data-install-auto on this script's tag).
     * Fires lh_web_application:install_shown, install_dismissed, install_accepted and app_installed
     * on window, with detail.trigger and detail.platform, for analytics plugins to record.
     * Sets the body class lh_web_application-is_installed once this browser has installed the app,
     * so the Install the App block and themes can hide install buttons there.
     */

    // This script's own tag carries its settings. The menu item can share its id, so ask for the script.
    var trigger_script = document.currentScript || document.querySelector( 'script#lh_web_application_trigger_app_install' );
    var sheet_id = 'lh_web_application-install';
    var storage_prefix = 'lh_web_application-install_';
    var deferred_prompt = null;
    var current_trigger = '';

    function attr( name, fallback ) {

        var value = trigger_script ? trigger_script.getAttribute( name ) : null;

        return ( null === value || '' === value ) ? fallback : value;

    }

    function is_standalone() {

        return true === window.navigator.standalone || window.matchMedia( '(display-mode: standalone)' ).matches;

    }

    // Which instructions the sheet shows.
    function platform() {

        var ua = window.navigator.userAgent || '';

        if ( deferred_prompt ) {
            return 'prompt';
        }

        // iPadOS reports itself as a Mac, so tell them apart by touch.
        if ( /iPad|iPhone|iPod/.test( ua ) || ( /Macintosh/.test( ua ) && window.navigator.maxTouchPoints > 1 ) ) {
            return 'ios';
        }

        if ( /Android/.test( ua ) ) {
            return 'android';
        }

        if ( /Macintosh/.test( ua ) && /Safari\//.test( ua ) && ! /Chrome|Chromium|Edg|Firefox/.test( ua ) ) {
            return 'macos';
        }

        return 'other';

    }

    function emit( name, detail ) {

        try {
            window.dispatchEvent( new CustomEvent( 'lh_web_application:' + name, { detail: detail } ) );
        } catch ( e ) {}

    }

    function storage_get( key ) {

        try {
            return window.localStorage.getItem( storage_prefix + key );
        } catch ( e ) {
            return null;
        }

    }

    function storage_set( key, value ) {

        try {
            window.localStorage.setItem( storage_prefix + key, value );
        } catch ( e ) {}

    }

    // Snoozed on this device, or for the signed-in account on any device.
    function snoozed() {

        var here = parseInt( storage_get( 'snoozed_until' ) || '0', 10 );
        var account = parseInt( attr( 'data-install-snoozed-until', '0' ), 10 ) * 1000;

        return Math.max( here, account ) > Date.now();

    }

    function tell_server( installed ) {

        var url = attr( 'data-install-snooze-url', '' );
        var nonce = attr( 'data-install-snooze-nonce', '' );

        if ( ! url || ! nonce || ! window.fetch || ! window.FormData ) {
            return;
        }

        var body = new FormData();
        body.append( 'action', attr( 'data-install-snooze-action', 'lh_web_application_install_snooze' ) );
        body.append( 'nonce', nonce );

        if ( installed ) {
            body.append( 'installed', '1' );
        }

        window.fetch( url, { method: 'POST', body: body, credentials: 'same-origin', keepalive: true } ).catch( function() {} );

    }

    function snooze() {

        var days = parseInt( attr( 'data-install-snooze-days', '30' ), 10 );

        storage_set( 'snoozed_until', String( Date.now() + days * 86400000 ) );
        tell_server( false );

    }

    function sheet() {

        return document.getElementById( sheet_id );

    }

    function open( trigger ) {

        var dialog = sheet();

        if ( ! dialog || is_standalone() ) {
            return false;
        }

        var shown_platform = platform();

        dialog.setAttribute( 'data-platform', shown_platform );
        dialog.returnValue = '';
        current_trigger = trigger;

        if ( dialog.open ) {
            return true;
        }

        // Shown on its own it leaves the page usable; asked for, it takes the focus.
        if ( 'auto' === trigger && dialog.show ) {
            dialog.show();
        } else if ( dialog.showModal ) {
            dialog.showModal();
        } else {
            dialog.setAttribute( 'open', '' );
        }

        emit( 'install_shown', { trigger: trigger, platform: shown_platform } );

        return true;

    }

    function close( reason ) {

        var dialog = sheet();

        if ( dialog && dialog.open ) {
            dialog.close( reason );
        }

    }

    function install() {

        if ( ! deferred_prompt ) {
            return;
        }

        var prompt_event = deferred_prompt;

        // The browser lets a prompt be used once.
        deferred_prompt = null;
        window.deferredPrompt = undefined;
        prompt_event.prompt();

        if ( prompt_event.userChoice ) {

            prompt_event.userChoice.then( function( choice ) {

                if ( choice && 'accepted' === choice.outcome ) {
                    emit( 'install_accepted', { trigger: current_trigger, platform: 'prompt' } );
                    close( 'accepted' );
                } else {
                    close( 'declined' );
                }

            } ).catch( function() {} );

        }

    }

    function handle_close() {

        var dialog = sheet();
        var reason = dialog ? dialog.returnValue : '';

        if ( 'accepted' !== reason ) {
            snooze();
            emit( 'install_dismissed', { trigger: current_trigger, platform: dialog ? dialog.getAttribute( 'data-platform' ) : '', reason: reason || 'dismissed' } );
        }

    }

    function handle_click( event ) {

        var target = event.target && event.target.closest ? event.target : null;

        if ( ! target ) {
            return;
        }

        var opener = target.closest( 'li.lh_web_application_trigger_app_install a, [data-lh-web-application-install]' );

        if ( opener ) {

            var trigger = opener.hasAttribute( 'data-lh-web-application-install' ) ? ( opener.getAttribute( 'data-lh-web-application-install' ) || 'link' ) : 'menu';

            if ( open( trigger ) ) {
                event.preventDefault();
            }

            return;

        }

        if ( target.closest( '#' + sheet_id + ' [data-install-action="install"]' ) ) {
            install();
            return;
        }

        if ( target.closest( '#' + sheet_id + ' [data-install-action="close"]' ) ) {
            close( 'dismissed' );
            return;
        }

        // A tap on the shaded backdrop lands on the dialog itself.
        if ( target.id === sheet_id ) {
            close( 'dismissed' );
        }

    }

    function handle_beforeinstallprompt( event ) {

        // Keep the browser's own mini-infobar away; the sheet offers the prompt instead.
        event.preventDefault();
        deferred_prompt = event;
        window.deferredPrompt = event;

        // A browser only offers its prompt while the app is not installed, so it has been removed.
        storage_set( 'installed', '0' );
        document.body.classList.remove( 'lh_web_application-is_installed' );

        document.body.classList.remove( 'not-lh_web_application_trigger_app_install' );
        document.body.classList.add( 'lh_web_application-prompt_stashed' );

        var dialog = sheet();

        if ( dialog && dialog.open ) {
            dialog.setAttribute( 'data-platform', 'prompt' );
        }

    }

    function handle_appinstalled() {

        deferred_prompt = null;
        storage_set( 'installed', '1' );
        tell_server( true );
        document.body.classList.add( 'not-lh_web_application_trigger_app_install' );
        document.body.classList.add( 'lh_web_application-is_installed' );
        emit( 'app_installed', { trigger: current_trigger || 'browser', platform: 'prompt' } );
        close( 'accepted' );

    }

    // Once a visitor keeps coming back on a phone, offer the app once by itself.
    function maybe_auto_open() {

        if ( '1' !== attr( 'data-install-auto', '0' ) || snoozed() || '1' === storage_get( 'installed' ) ) {
            return;
        }

        var visits = parseInt( storage_get( 'visits' ) || '0', 10 );

        // A visit is a browsing session, however many pages it has.
        try {

            if ( ! window.sessionStorage.getItem( storage_prefix + 'counted' ) ) {
                window.sessionStorage.setItem( storage_prefix + 'counted', '1' );
                visits += 1;
                storage_set( 'visits', String( visits ) );
            }

        } catch ( e ) {
            return;
        }

        if ( visits < parseInt( attr( 'data-install-auto-visits', '3' ), 10 ) ) {
            return;
        }

        if ( ! window.matchMedia( '(pointer: coarse)' ).matches ) {
            return;
        }

        window.setTimeout( function() {

            var shown_platform = platform();

            if ( ( 'ios' === shown_platform || 'android' === shown_platform || 'prompt' === shown_platform ) && ! snoozed() && ! document.body.classList.contains( 'lh_web_application-navigating' ) ) {
                open( 'auto' );
            }

        }, parseInt( attr( 'data-install-auto-delay', '4000' ), 10 ) );

    }

    function boot() {

        if ( ! document.body ) {
            return;
        }

        if ( is_standalone() ) {

            document.body.classList.add( 'lh_web_application-is_standalone' );

            // An app's storage is separate from the browser's, so tell the account it is installed.
            try {

                if ( ! window.sessionStorage.getItem( storage_prefix + 'reported' ) ) {
                    window.sessionStorage.setItem( storage_prefix + 'reported', '1' );
                    tell_server( true );
                }

            } catch ( e ) {}

            return;

        }

        // Installed from this browser before, so install buttons can hide.
        if ( '1' === storage_get( 'installed' ) ) {
            document.body.classList.add( 'lh_web_application-is_installed' );
        }

        window.addEventListener( 'beforeinstallprompt', handle_beforeinstallprompt );
        window.addEventListener( 'appinstalled', handle_appinstalled );
        document.addEventListener( 'click', handle_click );

        var dialog = sheet();

        if ( dialog ) {
            dialog.addEventListener( 'close', handle_close );
        }

        // Phones and tablets can always install, by instructions if not by prompt. Desktop browsers
        // show the menu item once they offer a prompt.
        if ( 'other' !== platform() ) {
            document.body.classList.remove( 'not-lh_web_application_trigger_app_install' );
            document.body.classList.add( 'lh_web_application-prompt_stashed' );
        }

        maybe_auto_open();

    }

    if ( 'loading' === document.readyState ) {
        document.addEventListener( 'DOMContentLoaded', boot );
    } else {
        boot();
    }

})();
