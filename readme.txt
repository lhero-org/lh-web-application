=== LH Web Application ===
Contributors: shawfactor
Tags: service worker, pwa, progressive web app, offline, web app manifest
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.38.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Make your WordPress sites into a modern web application.

== Description ==

LH Web Application turns a WordPress site into an installable progressive web app. It builds the web app manifest and the service worker from the site's own settings, so people can add the site to their home screen and keep reading the pages they have visited when their connection drops.

= Features =

* A web app manifest with the site name, a short name, theme and background colours, display mode, icons resized from the media library (maskable if you choose) and app shortcuts taken from a menu.
* A service worker that keeps a copy of each page visited for offline use, caches images and static files, and falls back to an offline page with your own message.
* A bar telling people when they are looking at a saved copy and when it was saved, with a Reload button once they are back online.
* Saved pages and images are cleared whenever someone signs out or a different person signs in, so a shared phone never shows the last member's pages.
* An install sheet that slides up from the bottom of the page and shows the right steps for each phone, tablet or browser, or offers the browser's own Install button. It can offer itself once to returning members on a phone, then stays away for 30 days after it is closed.
* An Install the App block, for placing an install button wherever a site wants to ask, such as a members' page or a registration confirmation. It hides itself inside the app and, by default, once the app is installed.
* In the installed app, a loading class for themes to draw a progress bar and, on iPhone and iPad, a floating Back button.
* Apple touch icons and iOS startup images, and an Install App menu item when LH Javascript Menu Items is active.
* Works on single sites and across a multisite network, with separate settings for each site.
* Other plugins can add to the manifest and the service worker, for example for push notifications or a share target.

= Getting started =

Activate the plugin, then go to Settings, Web Application to set a short name, choose the icons and colours, write the offline message, and choose who is offered the app and why. Without settings it uses the site icon and the theme's background colour, and offers the app to signed-in members of the site. If the manifest or service worker address returns a 404, save the permalinks once to refresh the rewrite rules.

* [Architecture](https://lhero.org/portfolio/lh-web-application/architecture/)
* [Extending](https://lhero.org/portfolio/lh-web-application/extending/)
* [Install the App block](https://lhero.org/portfolio/lh-web-application/install-the-app-block/)
* [FAQ](https://lhero.org/portfolio/lh-web-application/faq/)
* [Changelog](https://lhero.org/portfolio/lh-web-application/changelog/)

== Installation ==

1. Upload the `lh-web-application` folder to `/wp-content/plugins/`, or upload the plugin zip from Plugins > Add New.
2. Activate the plugin from the Plugins screen.

== Frequently Asked Questions ==

= Can I see a site using this plugin? =

Yes. [Princes Park Touch](https://princesparktouch.com/), a sports league site, runs on it and can be installed as an app.

= How can I check my site works as a progressive web app? =

* Open the site in Chrome on Android, or in Chrome or Edge on a desktop, and check that it offers to install it.
* In Chrome's developer tools, the Application panel shows the manifest (with any problems it finds), the service worker and what it has cached, and Lighthouse reports on the rest.
* The manifest and service worker addresses are listed at the top of Settings, Web Application. If either returns a 404, save the permalinks once.

= Does it work on iPhones and iPads? =

Yes. Safari uses the manifest, the service worker and the offline pages, and the plugin adds Apple touch icons and startup images. iOS has no automatic install prompt, so people add the site with Share, Add to Home Screen; the Install App menu item shows them how.

== Changelog ==

The full changelog is at https://lhero.org/portfolio/lh-web-application/changelog/.

= 1.38.2 =
* Cleanup with no visible change. The service worker no longer imports localForage: it imported the localforage script handle (registered by LH Localforage, when active), but no service worker code has used it. The unused bundled copy, assets/scripts/localforage.min.js, has been removed; it was never registered. Plugins that do need a script in the worker can still add its handle with the lh_web_application_import_scripts_handle_filter filter.

= 1.38.1 =
* Code quality fixes, with no change in behaviour. The install sheet's built-in styles are now added through WordPress's style API, as an inline style on the lh_web_application-install handle printed in the footer with the sheet, instead of being echoed as a raw style tag; they can still be changed with the lh_web_application_install_sheet_css filter. The offline page asks for its GMT date with the documented boolean argument, the script tag attribute filter asks for script tags in WP_HTML_Tag_Processor's documented array form, and a redundant empty check on the manifest shortcuts was removed.

= 1.38.0 =
* New Install the App block (lh-web-application/install-button) for asking people to install the app where it makes sense on a site, such as a members' page, a registration confirmation or a newsletter landing page. It shows a button styled by the theme that opens the install sheet, and its settings are the button text (default Install the app), alignment, a trigger name reported to analytics as detail.trigger on the sheet's events (default block; lower case letters, numbers, dashes and underscores), and Hide once installed (on by default), which hides the button in a browser that has installed the app. The button is always hidden inside the installed app itself, where it could do nothing, and when printing. The block is rendered on the server and its small stylesheet loads only on pages that use it. The install script now sets the body class lh_web_application-is_installed in a browser that has installed the app, and clears it again if the browser later offers to install it, meaning the app was removed.
