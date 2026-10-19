(function() {

// Kept for later: document.currentScript is only set while this script first runs.
var lh_web_application_script = document.currentScript;

var service_worker_url = lh_web_application_script ? lh_web_application_script.getAttribute('data-sw-url') : '';

if (service_worker_url && navigator.serviceWorker) {

    window.addEventListener('load', function() {

        navigator.serviceWorker.register(service_worker_url, {
            scope: '/'
        });

    });

}




/**
 * Clears the service worker's pages and images caches whenever the signed-in user changes:
 * logout, session expiry, or a different account. The marker is a hash printed
 * by the plugin in the head, empty when logged out.
 */
function lh_web_application_check_user_change(){

    var marker = document.querySelector('meta[name="lh-web-application-user"]');

    if (!marker || !('caches' in window)) {
        return;
    }

    var current = marker.getAttribute('content') || '';
    var key = 'lh_web_application-user';
    var stored;

    try {
        stored = window.localStorage.getItem(key);
    } catch (e) {
        return;
    }

    if (stored === current) {
        return;
    }

    caches.keys().then(function(names) {

        return Promise.all(names.filter(function(name) {
            return name.indexOf('lh_web_application-pages-') === 0 || name === 'lh_web_application-images';
        }).map(function(name) {
            return caches.delete(name);
        }));

    }).then(function() {

        try {
            window.localStorage.setItem(key, current);
        } catch (e) {}

    }).catch(function() {});

}

lh_web_application_check_user_change();


/**
 * Tells the visitor when they are looking at a saved copy of a page, served by
 * the service worker from its cache while offline, and offers a reload once the
 * connection is back. The wording comes from data attributes on this script tag
 * so it can be translated; themes can restyle the bar or use the body class.
 */
function lh_web_application_cached_notice(){

    var marker = document.querySelector('meta[name="lh-web-application-cached"]');

    if (!marker || !document.body) {
        return;
    }

    var script = lh_web_application_script || document.getElementById('lh_web_application-main-js');
    var attr = function(name, fallback) {
        return (script && script.getAttribute(name)) || fallback;
    };

    var saved = new Date(marker.getAttribute('content'));
    var message;

    if (isNaN(saved.getTime())) {
        message = attr('data-offline-notice-undated', 'You are offline. This is a saved copy.');
    } else {
        var when = saved.toLocaleString([], { weekday: 'short', day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });
        message = attr('data-offline-notice', 'You are offline. This is a saved copy from %s.').replace('%s', when);
    }

    document.body.classList.add('lh_web_application-served-from-cache');

    if (!document.getElementById('lh_web_application-cached_notice_style')) {
        var style = document.createElement('style');
        style.id = 'lh_web_application-cached_notice_style';
        // :where() keeps specificity at zero, so any theme rule wins.
        style.textContent = ':where(#lh_web_application-cached_notice){position:sticky;top:0;z-index:100000;padding:.5em 1em;background:#333;color:#fff;text-align:center;font-size:.9em}:where(#lh_web_application-cached_notice button){margin-left:.75em}';
        document.head.appendChild(style);
    }

    var notice = document.createElement('div');
    notice.id = 'lh_web_application-cached_notice';
    notice.setAttribute('role', 'status');

    var text = document.createElement('span');
    text.textContent = message;
    notice.appendChild(text);

    document.body.insertBefore(notice, document.body.firstChild);

    window.addEventListener('online', function() {

        text.textContent = attr('data-online-notice', 'You are back online.');

        if (!notice.querySelector('button')) {
            var button = document.createElement('button');
            button.type = 'button';
            button.textContent = attr('data-reload-label', 'Reload');
            button.addEventListener('click', function() {
                window.location.reload();
            });
            notice.appendChild(button);
        }

    });

}

lh_web_application_cached_notice();


// Body classes for themes: online or offline, and standalone when running as an installed app.
window.addEventListener('load', function() {

    function lh_web_application_handleConnectionChange(event) {

        if (event.type == 'offline') {
            document.body.classList.remove('online');
            document.body.classList.add('offline');
        }

        if (event.type == 'online') {
            document.body.classList.remove('offline');
            document.body.classList.add('online');
        }

    }

    var connDiv = document.createElement('div');
    connDiv.setAttribute('id', 'lh_web_application-connection_status');
    document.body.appendChild(connDiv);

    window.addEventListener('online', lh_web_application_handleConnectionChange);
    window.addEventListener('offline', lh_web_application_handleConnectionChange);

});

if (window.matchMedia('(display-mode: standalone)').matches && document.body) {

    document.body.classList.add('standalone');

}

/**
 * Adds lh_web_application-navigating to the body while the next page loads, so a theme
 * can show its own loading indicator. Only in the installed app, which has no browser one:
 * a browser tab already shows its own. Set as soon as a link to another page here is tapped
 * or a form is submitted, and from the Navigation API where the browser has it (back,
 * forward and script navigations).
 */
function lh_web_application_is_installed_app(){

    // navigator.standalone is set only by iOS, and only in an app opened from the home screen.
    return true === window.navigator.standalone
        || window.matchMedia('(display-mode: standalone)').matches
        || window.matchMedia('(display-mode: fullscreen)').matches;

}

function lh_web_application_navigating(){

    var navigating_class = 'lh_web_application-navigating';
    var safety_timer = 0;

    if (!document.body || !lh_web_application_is_installed_app()) {
        return;
    }

    function start() {
        document.body.classList.add(navigating_class);
        // Never leave the indicator up if no new page arrives, as when a link only downloads a file.
        window.clearTimeout(safety_timer);
        safety_timer = window.setTimeout(stop, 30000);
    }

    function stop() {
        window.clearTimeout(safety_timer);
        document.body.classList.remove(navigating_class);
    }

    function opens_elsewhere(target) {
        return !!target && '_self' !== target.toLowerCase();
    }

    // Set on the tap itself, before the request starts, so the indicator is drawn even where
    // the old page stops repainting once the navigation begins. Listening on window in the
    // bubble phase lets any other script cancel the click or submit first.
    window.addEventListener('click', function(event) {

        if (event.defaultPrevented || 0 !== event.button || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;

        if (!link || link.hasAttribute('download') || opens_elsewhere(link.getAttribute('target'))) {
            return;
        }

        var url;

        try {
            url = new URL(link.href, window.location.href);
        } catch (e) {
            return;
        }

        // Other sites open outside the app, and a link to a place on this page loads nothing.
        if (url.origin !== window.location.origin || ('http:' !== url.protocol && 'https:' !== url.protocol)) {
            return;
        }

        if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) {
            return;
        }

        start();

    });

    window.addEventListener('submit', function(event) {

        var form = event.target;
        var submitter = event.submitter;

        if (event.defaultPrevented || !form || opens_elsewhere(form.getAttribute('target')) || (submitter && opens_elsewhere(submitter.getAttribute('formtarget')))) {
            return;
        }

        start();

    });

    if ('navigation' in window) {

        window.navigation.addEventListener('navigate', function(event) {

            // In-page anchors and downloads never load a new page.
            if (event.hashChange || 'string' === typeof event.downloadRequest) {
                stop();
                return;
            }

            start();

        });

        window.navigation.addEventListener('navigatesuccess', stop);
        window.navigation.addEventListener('navigateerror', stop);

    }

    window.addEventListener('hashchange', stop);

    // A page restored from the back/forward cache keeps its old classes.
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            stop();
        }
    });

}

lh_web_application_navigating();

/**
 * A floating Back button for an installed app, which has no browser back button. By default
 * only on iPhone and iPad: Android has its own. Shown only while there is a page in this app's
 * history to go back to, and tucked away while scrolling down or typing. The page gets
 * lh_web_application-has_back while it shows. Themes restyle #lh_web_application-back or the
 * --lh-web-application-back-* custom properties; the lh_web_application_back_button filter
 * picks where it appears (ios, standalone or none).
 */
function lh_web_application_back_button(){

    var script = lh_web_application_script || document.getElementById('lh_web_application-main-js');
    var mode = script ? script.getAttribute('data-back-button') : 'ios';
    var button_id = 'lh_web_application-back';
    var tucked_class = 'lh_web_application-back_tucked';

    if (!document.body || document.getElementById(button_id) || !('navigation' in window)) {
        return;
    }

    // navigator.standalone is set only by iOS, and only in an app opened from the home screen.
    if ('ios' === mode) {
        if (true !== window.navigator.standalone) {
            return;
        }
    } else if ('standalone' === mode) {
        if (!window.matchMedia('(display-mode: standalone)').matches && true !== window.navigator.standalone) {
            return;
        }
    } else {
        return;
    }

    if (!document.getElementById(button_id + '_style')) {
        var style = document.createElement('style');
        style.id = button_id + '_style';
        // Id selectors, so a theme's general button rules (padding, colours, hover and focus
        // states) cannot leak in. Restyle with the custom properties or a rule naming the id.
        style.textContent = '#lh_web_application-back,#lh_web_application-back:hover,#lh_web_application-back:focus,#lh_web_application-back:active{position:fixed;left:calc(env(safe-area-inset-left,0px) + 16px);bottom:calc(env(safe-area-inset-bottom,0px) + 16px);z-index:99999;display:flex;align-items:center;justify-content:center;box-sizing:border-box;width:var(--lh-web-application-back-size,48px);height:var(--lh-web-application-back-size,48px);min-width:0;min-height:0;max-width:none;margin:0;padding:0;border:var(--lh-web-application-back-border,0);border-radius:50%;background:var(--lh-web-application-back-background,rgba(0,0,0,.75));color:var(--lh-web-application-back-color,#fff);box-shadow:0 2px 8px rgba(0,0,0,.35);font:inherit;line-height:1;letter-spacing:normal;text-transform:none;outline:0;-webkit-appearance:none;appearance:none;cursor:pointer;-webkit-tap-highlight-color:transparent;transition:opacity .2s ease,transform .2s ease}'
            + '#lh_web_application-back:focus-visible{outline:2px solid var(--lh-web-application-back-background,#000);outline-offset:3px}'
            + '#lh_web_application-back[hidden]{display:none}'
            + '#lh_web_application-back.lh_web_application-back_tucked{opacity:0;transform:translateY(calc(100% + 16px));pointer-events:none}'
            + '#lh_web_application-back svg{display:block;flex:none;width:60%;height:60%;margin:0;padding:0}'
            + '@media (prefers-reduced-motion:reduce){#lh_web_application-back,#lh_web_application-back:hover,#lh_web_application-back:focus{transition:none}}'
            + '@media print{#lh_web_application-back{display:none}}';
        document.head.appendChild(style);
    }

    var button = document.createElement('button');
    button.type = 'button';
    button.id = button_id;
    button.hidden = true;
    button.setAttribute('aria-label', (script && script.getAttribute('data-back-label')) || 'Back');
    button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 4.5 7.5 12l7.5 7.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    button.addEventListener('click', function() {
        if (window.navigation.canGoBack) {
            window.history.back();
        }
    });

    function update() {
        var can_go_back = !!window.navigation.canGoBack;
        button.hidden = !can_go_back;
        document.body.classList.toggle('lh_web_application-has_back', can_go_back);
    }

    // Out of the way while reading down the page or typing; back on scrolling up.
    var typing = false;
    var scrolling_down = false;
    var last_y = window.scrollY;
    var queued = false;

    function tuck() {
        button.classList.toggle(tucked_class, typing || scrolling_down);
    }

    window.addEventListener('scroll', function() {
        if (queued) {
            return;
        }
        queued = true;
        window.requestAnimationFrame(function() {
            var y = window.scrollY;
            if (y <= 80) {
                scrolling_down = false;
            } else if (y > last_y + 6) {
                scrolling_down = true;
            } else if (y < last_y - 6) {
                scrolling_down = false;
            }
            last_y = y;
            queued = false;
            tuck();
        });
    }, { passive: true });

    function is_text_field(element) {
        if (!element || !element.matches) {
            return false;
        }
        return element.matches('textarea, select, [contenteditable=""], [contenteditable="true"], input:not([type="button"]):not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="reset"]):not([type="image"]):not([type="file"]):not([type="range"]):not([type="color"])');
    }

    document.addEventListener('focusin', function(event) {
        typing = is_text_field(event.target);
        tuck();
    });

    document.addEventListener('focusout', function() {
        typing = false;
        tuck();
    });

    window.navigation.addEventListener('currententrychange', update);
    window.addEventListener('pageshow', update);

    document.body.appendChild(button);
    update();

}

lh_web_application_back_button();

// Lets the service worker and its includes refresh content once a day, where supported.
if ('serviceWorker' in navigator) {

    navigator.serviceWorker.ready.then(function(registration) {

        if ('periodicSync' in registration) {

            registration.periodicSync.register('update', {
                minInterval: 24 * 60 * 60 * 1000
            }).catch(function() {});

        }

    });

}

})();
