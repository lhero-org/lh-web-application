<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Included from LH_web_application_service_worker_class::maybe_serve_service_worker() to print the service worker.

$precache_offline_urls = array( self::return_offline_page_url() );
$precache_page_urls    = array( self::return_start_url() );

// Changing the offline message renames the offline cache, so a new worker refetches it.
$buster = wp_hash( self::return_plugin_namespace() . wp_json_encode( $precache_page_urls ) . wp_json_encode( $precache_offline_urls ) . self::return_offline_html() );

$precache_static_urls = self::return_precache_static_urls_as_array();

// Unused static files (CSS, JS, fonts) are dropped after this many days; 0 keeps them.
$static_max_age_days = absint( apply_filters( self::return_plugin_namespace() . '_static_max_age_days', 180 ) );

// Images are kept however old, but only this many; the least recently used go first.
$images_max_entries = max( 1, absint( apply_filters( self::return_plugin_namespace() . '_images_max_entries', 150 ) ) );

// Saved pages are kept however old, but only this many; the least recently used go first.
// The start URL and the offline page are never dropped.
$pages_max_entries = max( 1, absint( apply_filters( self::return_plugin_namespace() . '_pages_max_entries', 300 ) ) );

do_action( self::return_plugin_namespace() . '_open_service_worker' );

echo "'use strict';\n\n";

echo 'const PAGES_CACHENAME = ' . wp_json_encode( 'lh_web_application-pages-' . $buster ) . ";\n";
echo 'const PRECACHE_PAGE_URLS = ' . wp_json_encode( $precache_page_urls ) . ";\n";
echo 'const OFFLINE_CACHENAME = ' . wp_json_encode( 'lh_web_application-offline-' . $buster ) . ";\n";
echo 'const PRECACHE_OFFLINE_URLS = ' . wp_json_encode( $precache_offline_urls ) . ";\n";
echo 'const STATIC_CACHENAME = ' . wp_json_encode( 'lh_web_application-static' ) . ";\n";
echo 'const IMAGES_CACHENAME = ' . wp_json_encode( 'lh_web_application-images' ) . ";\n";
echo 'const PRECACHE_STATIC_URLS = ' . wp_json_encode( $precache_static_urls ) . ";\n";
echo 'const HTML_FALLBACK_URL = ' . wp_json_encode( self::return_offline_page_url() ) . ";\n";
echo 'const STATIC_MAX_AGE_DAYS = ' . wp_json_encode( $static_max_age_days ) . ";\n";
echo 'const IMAGES_MAX_ENTRIES = ' . wp_json_encode( $images_max_entries ) . ";\n";
echo 'const PAGES_MAX_ENTRIES = ' . wp_json_encode( $pages_max_entries ) . ";\n";

?>

// Add each URL on its own, so one failed URL cannot stop the worker installing.
function lh_web_application_precache( cache_name, urls ) {
  return caches.open( cache_name ).then( function( cache ) {
    return Promise.all( urls.map( function( url ) {
      return cache.add( url ).catch( function() {} );
    } ) );
  } );
}

self.addEventListener( 'install', function( event ) {
  event.waitUntil(
    Promise.all( [
      lh_web_application_precache( OFFLINE_CACHENAME, PRECACHE_OFFLINE_URLS ),
      lh_web_application_precache( PAGES_CACHENAME, PRECACHE_PAGE_URLS ),
      lh_web_application_precache( STATIC_CACHENAME, PRECACHE_STATIC_URLS )
    ] ).then( function() {
      return self.skipWaiting();
    } )
  );
} );

// Remove only this plugin's caches from older workers; other caches on the origin are left alone.
self.addEventListener( 'activate', function( event ) {
  const currentCaches = [ PAGES_CACHENAME, OFFLINE_CACHENAME, STATIC_CACHENAME, IMAGES_CACHENAME ];
  event.waitUntil(
    caches.keys().then( function( cacheNames ) {
      return Promise.all( cacheNames.filter( function( cacheName ) {
        return cacheName.indexOf( 'lh_web_application-' ) === 0 && currentCaches.indexOf( cacheName ) === -1;
      } ).map( function( cacheName ) {
        return caches.delete( cacheName );
      } ) );
    } ).then( function() {
      return self.clients.claim();
    } )
  );
} );
<?php

echo 'var admin_ajax_return_wp_nonce = ' . wp_json_encode( add_query_arg( 'action', 'return_wp_nonce', admin_url( 'admin-ajax.php' ) ) ) . ";\n\n";

do_action( self::return_plugin_namespace() . '_pass_variables_action' );

$scripts_array = apply_filters( self::return_plugin_namespace() . '_import_scripts_src_filter', self::get_import_scripts_array() );

foreach ( $scripts_array as $script_src ) {

    echo 'importScripts(' . wp_json_encode( apply_filters( 'script_loader_src', $script_src, '' ) ) . ");\n";

}

// An end marker for page caches that only store responses ending in </html>.
echo '//</html>';
