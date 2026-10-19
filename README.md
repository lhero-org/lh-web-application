# LH Web Application

Make your WordPress sites into a modern web application.

## Description

LH Web Application turns a WordPress site into an installable progressive web app. It builds the web app manifest and the service worker from the site's own settings, so people can add the site to their home screen and keep reading the pages they have visited when their connection drops.

### Features

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

### Getting started

Activate the plugin, then go to Settings, Web Application to set a short name, choose the icons and colours, write the offline message, and choose who is offered the app and why. Without settings it uses the site icon and the theme's background colour, and offers the app to signed-in members of the site. If the manifest or service worker address returns a 404, save the permalinks once to refresh the rewrite rules.

* [Architecture](https://lhero.org/portfolio/lh-web-application/architecture/)
* [Extending](https://lhero.org/portfolio/lh-web-application/extending/)
* [Install the App block](https://lhero.org/portfolio/lh-web-application/install-the-app-block/)
* [FAQ](https://lhero.org/portfolio/lh-web-application/faq/)
* [Changelog](https://lhero.org/portfolio/lh-web-application/changelog/)

## Installation

1. Upload the `lh-web-application` folder to `/wp-content/plugins/`, or upload the plugin zip from Plugins > Add New.
2. Activate the plugin from the Plugins screen.

## Links

* [Documentation](https://lhero.org/portfolio/lh-web-application/)
* [Changelog](https://lhero.org/portfolio/lh-web-application/changelog/)
