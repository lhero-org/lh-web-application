/**
 * LH Web Application service worker fetch handling.
 *
 * Imported into the service worker by partials/sw4.php, which defines
 * PAGES_CACHENAME, STATIC_CACHENAME, IMAGES_CACHENAME, HTML_FALLBACK_URL,
 * PRECACHE_PAGE_URLS, STATIC_MAX_AGE_DAYS, IMAGES_MAX_ENTRIES and
 * PAGES_MAX_ENTRIES first.
 *
 * Images and other static assets are served cache first. Pages are served network first
 * (using the navigation preload response where the browser supports it), and a
 * copy of each successful same-origin HTML page is kept in the pages cache so
 * it can be shown offline. The page script clears the pages and images caches whenever the
 * signed-in user changes (see lh-web-application-main.js).
 *
 * Static, image and page entries record when they were last used. Static files unused
 * for STATIC_MAX_AGE_DAYS are dropped (replaced ?ver= copies age out this way).
 * Images and pages are kept however old, but only IMAGES_MAX_ENTRIES and
 * PAGES_MAX_ENTRIES of them, dropping the least recently used first; the start
 * URL and the offline page are never dropped. If IndexedDB is unavailable nothing
 * is lost: static files simply stay, and the capped caches drop the oldest added instead.
 */

var LH_WA_DAY = 24 * 60 * 60 * 1000;
var LH_WA_STATIC_MAX_AGE_DAYS = ( typeof STATIC_MAX_AGE_DAYS !== 'undefined' ) ? STATIC_MAX_AGE_DAYS : 180;
var LH_WA_IMAGES_MAX_ENTRIES = ( typeof IMAGES_MAX_ENTRIES !== 'undefined' ) ? IMAGES_MAX_ENTRIES : 150;
var LH_WA_PAGES_MAX_ENTRIES = ( typeof PAGES_MAX_ENTRIES !== 'undefined' ) ? PAGES_MAX_ENTRIES : 300;

// Pages the cap never drops: the start URL and the offline page.
var LH_WA_PAGES_KEEP = ( ( typeof PRECACHE_PAGE_URLS !== 'undefined' ) ? PRECACHE_PAGE_URLS : [] ).concat( ( typeof HTML_FALLBACK_URL !== 'undefined' ) ? [ HTML_FALLBACK_URL ] : [] );

var lh_wa_db_promise = null;

// Last-used times this worker has already recorded, so a busy page does not write on every request.
var lh_wa_recent = {};

function lh_wa_open_db() {

	if ( lh_wa_db_promise ) {
		return lh_wa_db_promise;
	}

	lh_wa_db_promise = new Promise( function( resolve, reject ) {

		if ( ! self.indexedDB ) {
			reject( new Error( 'IndexedDB is not available' ) );
			return;
		}

		var open = self.indexedDB.open( 'lh_web_application', 1 );

		open.onupgradeneeded = function() {

			var db = open.result;

			if ( ! db.objectStoreNames.contains( 'last_used' ) ) {
				db.createObjectStore( 'last_used', { keyPath: 'key' } ).createIndex( 'cache', 'cache' );
			}

			if ( ! db.objectStoreNames.contains( 'meta' ) ) {
				db.createObjectStore( 'meta' );
			}

		};

		open.onsuccess = function() {
			resolve( open.result );
		};

		open.onerror = function() {
			reject( open.error );
		};

	} ).catch( function( error ) {
		lh_wa_db_promise = null;
		throw error;
	} );

	return lh_wa_db_promise;

}

/**
 * Runs work( tx, result ) in one transaction and resolves with result.value
 * once the transaction completes.
 */
function lh_wa_transaction( stores, mode, work ) {

	return lh_wa_open_db().then( function( db ) {

		return new Promise( function( resolve, reject ) {

			var tx = db.transaction( stores, mode );
			var result = {};

			tx.oncomplete = function() {
				resolve( result.value );
			};

			tx.onerror = tx.onabort = function() {
				reject( tx.error );
			};

			work( tx, result );

		} );

	} );

}

function lh_wa_key( cache_name, url ) {

	return cache_name + ' ' + url;

}

// Records that urls in cache_name were used now.
function lh_wa_record( cache_name, urls ) {

	var now = Date.now();

	if ( ! urls.length ) {
		return Promise.resolve();
	}

	return lh_wa_transaction( [ 'last_used' ], 'readwrite', function( tx ) {

		var store = tx.objectStore( 'last_used' );

		urls.forEach( function( url ) {
			store.put( { key: lh_wa_key( cache_name, url ), cache: cache_name, url: url, time: now } );
			lh_wa_recent[ lh_wa_key( cache_name, url ) ] = now;
		} );

	} );

}

// Like lh_wa_record() for one URL, but writes at most once a day per URL.
function lh_wa_touch( cache_name, url ) {

	var key = lh_wa_key( cache_name, url );
	var now = Date.now();

	if ( lh_wa_recent[ key ] && ( now - lh_wa_recent[ key ] ) < LH_WA_DAY ) {
		return Promise.resolve();
	}

	return lh_wa_transaction( [ 'last_used' ], 'readwrite', function( tx ) {

		var store = tx.objectStore( 'last_used' );
		var get = store.get( key );

		get.onsuccess = function() {

			var record = get.result;

			if ( record && ( now - record.time ) < LH_WA_DAY ) {
				lh_wa_recent[ key ] = record.time;
				return;
			}

			store.put( { key: key, cache: cache_name, url: url, time: now } );
			lh_wa_recent[ key ] = now;

		};

	} ).catch( function() {} );

}

// Resolves with { url: last used time } for every recorded entry of cache_name.
function lh_wa_times( cache_name ) {

	return lh_wa_transaction( [ 'last_used' ], 'readonly', function( tx, result ) {

		var get = tx.objectStore( 'last_used' ).index( 'cache' ).getAll( cache_name );

		get.onsuccess = function() {

			result.value = {};

			get.result.forEach( function( record ) {
				result.value[ record.url ] = record.time;
			} );

		};

	} );

}

function lh_wa_forget( cache_name, urls ) {

	if ( ! urls.length ) {
		return Promise.resolve();
	}

	return lh_wa_transaction( [ 'last_used' ], 'readwrite', function( tx ) {

		var store = tx.objectStore( 'last_used' );

		urls.forEach( function( url ) {
			store.delete( lh_wa_key( cache_name, url ) );
			delete lh_wa_recent[ lh_wa_key( cache_name, url ) ];
		} );

	} );

}

/**
 * Brings the records for cache_name in line with the cache: entries with no
 * record (cached before tracking began) start their clock now, records whose
 * entry has gone are removed, and with max_age_days set, entries unused for
 * longer than that are deleted.
 */
function lh_wa_sweep_cache( cache_name, max_age_days ) {

	var now = Date.now();

	return Promise.all( [ caches.open( cache_name ), lh_wa_times( cache_name ) ] ).then( function( opened ) {

		var cache = opened[0];
		var times = opened[1];

		return cache.keys().then( function( requests ) {

			var cached = {};
			var unrecorded = [];
			var stale = [];

			requests.forEach( function( request ) {

				cached[ request.url ] = true;

				if ( ! ( request.url in times ) ) {
					unrecorded.push( request.url );
				} else if ( max_age_days && ( now - times[ request.url ] ) > max_age_days * LH_WA_DAY ) {
					stale.push( request );
				}

			} );

			var gone = Object.keys( times ).filter( function( url ) {
				return ! cached[ url ];
			} );

			return Promise.all( stale.map( function( request ) {
				return cache.delete( request );
			} ) ).then( function() {
				return lh_wa_forget( cache_name, gone.concat( stale.map( function( request ) {
					return request.url;
				} ) ) );
			} ).then( function() {
				return lh_wa_record( cache_name, unrecorded );
			} );

		} );

	} );

}

function lh_wa_sweep() {

	return Promise.all( [
		lh_wa_sweep_cache( STATIC_CACHENAME, LH_WA_STATIC_MAX_AGE_DAYS ),
		lh_wa_sweep_cache( IMAGES_CACHENAME, 0 ),
		lh_wa_sweep_cache( PAGES_CACHENAME, 0 )
	] ).then( function() {

		return lh_wa_transaction( [ 'meta' ], 'readwrite', function( tx ) {
			tx.objectStore( 'meta' ).put( Date.now(), 'last_sweep' );
		} );

	} );

}

var lh_wa_sweep_checked = false;

// Sweeps when a new worker activates, and at most once a day while one keeps running.
function lh_wa_maybe_sweep( event ) {

	if ( lh_wa_sweep_checked ) {
		return;
	}

	lh_wa_sweep_checked = true;

	event.waitUntil(

		lh_wa_transaction( [ 'meta' ], 'readonly', function( tx, result ) {

			var get = tx.objectStore( 'meta' ).get( 'last_sweep' );

			get.onsuccess = function() {
				result.value = get.result;
			};

		} ).then( function( last_sweep ) {

			if ( last_sweep && ( Date.now() - last_sweep ) < LH_WA_DAY ) {
				return;
			}

			return lh_wa_sweep();

		} ).catch( function() {} )

	);

}

self.addEventListener( 'activate', function( event ) {

	lh_wa_sweep_checked = true;

	event.waitUntil( Promise.all( [
		lh_wa_sweep().catch( function() {} ),
		lh_wa_enable_navigation_preload()
	] ) );

} );

/**
 * Navigation preload: the browser starts fetching a page while the worker is
 * still starting up, which saves a noticeable delay on slow phones. Pages read
 * the response from event.preloadResponse (see lh_wa_navigation_response()).
 */
function lh_wa_enable_navigation_preload() {

	if ( ! self.registration || ! self.registration.navigationPreload ) {
		return Promise.resolve();
	}

	return self.registration.navigationPreload.enable().catch( function() {} );

}

// The preloaded response for a navigation if there is one, otherwise a normal fetch.
function lh_wa_navigation_response( event ) {

	var request = event.request;

	if ( request.mode !== 'navigate' || ! event.preloadResponse ) {
		return fetch( request );
	}

	return Promise.resolve( event.preloadResponse ).then( function( preloaded ) {
		return preloaded || fetch( request );
	}, function() {
		return fetch( request );
	} );

}

/**
 * A navigation this worker does not handle (wp-admin, wp-login, the REST API,
 * nonce URLs) still answers from its preload, so the page is not requested twice.
 */
function lh_wa_pass_through( event ) {

	if ( event.request.mode === 'navigate' && event.preloadResponse ) {
		event.respondWith( lh_wa_navigation_response( event ) );
	}

}

function getFileExt( url ) {

	url = url.substring( 0, ( url.indexOf( '#' ) === -1 ) ? url.length : url.indexOf( '#' ) );
	url = url.substring( 0, ( url.indexOf( '?' ) === -1 ) ? url.length : url.indexOf( '?' ) );
	url = url.substring( url.lastIndexOf( '/' ) + 1, url.length );

	var a = url.split( '.' );

	if ( a.length === 1 || ( a[0] === '' && a.length === 2 ) ) {
		return '';
	}

	return a.pop().toLowerCase();

}

function isStaticUrl( url ) {

	var static_extensions = [ 'js', 'css', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'woff', 'woff2', 'svg', 'ico' ];

	return static_extensions.indexOf( getFileExt( url ) ) !== -1;

}

/**
 * Requests that must never be answered from, or stored in, the pages cache:
 * other origins, wp-admin (including admin-ajax), the REST API, login, and
 * any URL carrying a nonce.
 */
function isExcludedFromPagesCache( url ) {

	var parsed;

	try {
		parsed = new URL( url );
	} catch ( e ) {
		return true;
	}

	if ( parsed.origin !== self.location.origin ) {
		return true;
	}

	var path = parsed.pathname;

	if ( path.indexOf( '/wp-admin' ) !== -1 || path.indexOf( '/wp-json/' ) !== -1 || path.indexOf( 'wp-login.php' ) !== -1 ) {
		return true;
	}

	if ( parsed.searchParams.has( 'rest_route' ) ) {
		return true;
	}

	var has_nonce = false;

	parsed.searchParams.forEach( function( value, key ) {
		if ( key.toLowerCase().indexOf( 'nonce' ) !== -1 ) {
			has_nonce = true;
		}
	} );

	return has_nonce;

}

/**
 * Only successful same-origin HTML pages that do not ask not to be stored.
 */
function isCacheablePageResponse( response ) {

	if ( ! response || response.status !== 200 || response.type !== 'basic' ) {
		return false;
	}

	var content_type = response.headers.get( 'content-type' ) || '';

	if ( content_type.indexOf( 'text/html' ) === -1 ) {
		return false;
	}

	var cache_control = ( response.headers.get( 'cache-control' ) || '' ).toLowerCase();

	return cache_control.indexOf( 'no-store' ) === -1;

}

function isPageNavigation( request ) {

	if ( request.mode === 'navigate' ) {
		return true;
	}

	var accept = request.headers.get( 'accept' ) || '';

	return accept.indexOf( 'text/html' ) !== -1;

}

function offlineFallbackResponse() {

	return new Response( 'You are offline.', {
		status: 503,
		headers: { 'Content-Type': 'text/plain; charset=utf-8', 'Cache-Control': 'no-store' }
	} );

}

/**
 * Images get their own capped cache, cleared with the pages cache when the
 * signed-in user changes. Cross-origin images (Photon, Gravatar) are fetched by
 * the worker in CORS mode, because the browser's own no-cors image request
 * returns an opaque response that cannot be checked or sensibly cached.
 */

function isImageRequest( request ) {

	if ( request.destination === 'image' ) {
		return true;
	}

	return [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'ico' ].indexOf( getFileExt( request.url ) ) !== -1;

}

function isCacheableImageResponse( response ) {

	if ( ! response || ! response.ok || ( response.type !== 'basic' && response.type !== 'cors' ) ) {
		return false;
	}

	var content_type = response.headers.get( 'content-type' ) || '';

	if ( content_type.indexOf( 'image/' ) !== 0 ) {
		return false;
	}

	var cache_control = ( response.headers.get( 'cache-control' ) || '' ).toLowerCase();

	return cache_control.indexOf( 'no-store' ) === -1 && cache_control.indexOf( 'private' ) === -1;

}

function fetchImage( request ) {

	if ( new URL( request.url ).origin === self.location.origin ) {
		return fetch( request );
	}

	var cors_request = new Request( request.url, {
		mode: 'cors',
		credentials: 'omit',
		headers: { 'Accept': request.headers.get( 'accept' ) || 'image/*' }
	} );

	// A host that does not allow CORS still gets the browser's own request, just not cached.
	return fetch( cors_request ).catch( function() {
		return fetch( request );
	} );

}

// Drops the oldest added entries beyond max_entries, never one whose URL is in keep.
function trimCache( cache, max_entries, keep ) {

	keep = keep || [];

	return cache.keys().then( function( keys ) {

		if ( keys.length <= max_entries ) {
			return;
		}

		// Keys come back oldest first, so drop from the front.
		var droppable = keys.filter( function( key ) {
			return keep.indexOf( key.url ) === -1;
		} );

		return Promise.all( droppable.slice( 0, Math.max( 0, keys.length - max_entries ) ).map( function( key ) {
			return cache.delete( key );
		} ) );

	} );

}

/**
 * Keeps cache_name to max_entries, dropping the least recently used first.
 * Entries whose URL is in keep are never dropped, though they count towards the cap.
 */
function lh_wa_trim_least_recently_used( cache, cache_name, max_entries, keep ) {

	keep = keep || [];

	return cache.keys().then( function( requests ) {

		if ( requests.length <= max_entries ) {
			return;
		}

		return lh_wa_times( cache_name ).then( function( times ) {

			var ordered = requests.map( function( request, position ) {
				return { request: request, time: times[ request.url ] || 0, position: position };
			} ).filter( function( entry ) {
				return keep.indexOf( entry.request.url ) === -1;
			} );

			ordered.sort( function( a, b ) {
				return ( a.time - b.time ) || ( a.position - b.position );
			} );

			var dropped = ordered.slice( 0, Math.max( 0, requests.length - max_entries ) ).map( function( entry ) {
				return entry.request;
			} );

			return Promise.all( dropped.map( function( request ) {
				return cache.delete( request );
			} ) ).then( function() {
				return lh_wa_forget( cache_name, dropped.map( function( request ) {
					return request.url;
				} ) );
			} );

		}, function() {
			return trimCache( cache, max_entries, keep );
		} );

	} );

}

// Keeps the images cache to its cap, dropping the least recently used first.
function trimImages( cache ) {

	return lh_wa_trim_least_recently_used( cache, IMAGES_CACHENAME, LH_WA_IMAGES_MAX_ENTRIES );

}

// Keeps the pages cache to its cap, dropping the least recently used first but never the start URL or offline page.
function trimPages( cache ) {

	return lh_wa_trim_least_recently_used( cache, PAGES_CACHENAME, LH_WA_PAGES_MAX_ENTRIES, LH_WA_PAGES_KEEP );

}

function offlineImageResponse() {

	return new Response( '<svg role="img" aria-labelledby="offline-title" viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg"><title id="offline-title">Offline</title><g fill="none" fill-rule="evenodd"><path fill="#D8D8D8" d="M0 0h400v300H0z"/><text fill="#9B9B9B" font-family="Helvetica Neue,Arial,Helvetica,sans-serif" font-size="72" font-weight="bold"><tspan x="93" y="172">offline</tspan></text></g></svg>', { headers: { 'Content-Type': 'image/svg+xml', 'Cache-Control': 'no-store' } } );

}

function handle_image_request( event ) {

	var request = event.request;

	event.respondWith(

		caches.open( IMAGES_CACHENAME ).then( function( cache ) {

			return cache.match( request.url, { ignoreVary: true } ).then( function( cached ) {

				if ( cached ) {
					event.waitUntil( lh_wa_touch( IMAGES_CACHENAME, request.url ) );
					return cached;
				}

				return fetchImage( request ).then( function( response ) {

					if ( isCacheableImageResponse( response ) ) {

						var copy = response.clone();

						event.waitUntil(
							cache.put( request.url, copy ).then( function() {
								return lh_wa_record( IMAGES_CACHENAME, [ request.url ] ).catch( function() {} );
							} ).then( function() {
								return trimImages( cache );
							} ).catch( function() {} )
						);

					}

					return response;

				} );

			} );

		} ).catch( function() {
			return offlineImageResponse();
		} )

	);

}

/**
 * Marks a page served from the cache while offline, so the page script can say
 * it is a saved copy and when it was saved (the original response's Date header).
 * Only runs on the offline path. If anything goes wrong the page is served unmarked.
 */
function markCachedPage( response ) {

	var saved_at = ( response.headers.get( 'date' ) || '' ).replace( /[^0-9A-Za-z ,:]/g, '' );

	return response.clone().text().then( function( html ) {

		var meta = '<meta name="lh-web-application-cached" content="' + saved_at + '">';
		var marked = html.replace( /<head(\s[^>]*)?>/i, function( tag ) {
			return tag + meta;
		} );

		if ( marked === html ) {
			return response;
		}

		var headers = new Headers( response.headers );
		headers.delete( 'content-length' );
		headers.delete( 'content-encoding' );

		return new Response( marked, {
			status: response.status,
			statusText: response.statusText,
			headers: headers
		} );

	} ).catch( function() {
		return response;
	} );

}

self.addEventListener( 'fetch', function( event ) {

	var request = event.request;

	if ( request.method !== 'GET' ) {
		return;
	}

	if ( request.url.indexOf( '/wp-admin' ) !== -1 ) {
		lh_wa_pass_through( event );
		return;
	}

	lh_wa_maybe_sweep( event );

	if ( isImageRequest( request ) ) {
		handle_image_request( event );
		return;
	}

	if ( isStaticUrl( request.url ) ) {
		handle_possible_static_request( event );
		return;
	}

	if ( isExcludedFromPagesCache( request.url ) ) {
		lh_wa_pass_through( event );
		return;
	}

	handle_possible_dynamic_request( event );

} );

function handle_possible_dynamic_request( event ) {

	var request = event.request;

	event.respondWith(

		lh_wa_navigation_response( event ).then( function( response ) {

			if ( isCacheablePageResponse( response ) ) {

				var copy = response.clone();

				event.waitUntil(
					caches.open( PAGES_CACHENAME ).then( function( cache ) {
						return cache.put( request, copy ).then( function() {
							return lh_wa_touch( PAGES_CACHENAME, request.url );
						} ).then( function() {
							return trimPages( cache );
						} );
					} ).catch( function() {} )
				);

			}

			return response;

		} ).catch( function() {

			return caches.match( request ).then( function( cached ) {

				if ( cached ) {
					event.waitUntil( lh_wa_touch( PAGES_CACHENAME, request.url ) );
					return isPageNavigation( request ) ? markCachedPage( cached ) : cached;
				}

				if ( ! isPageNavigation( request ) ) {
					return Response.error();
				}

				return caches.match( HTML_FALLBACK_URL ).then( function( fallback ) {
					return fallback || offlineFallbackResponse();
				} );

			} );

		} )

	);

}

function handle_possible_static_request( event ) {

	var request = event.request;

	event.respondWith(

		caches.match( request ).then( function( cached ) {

			if ( cached ) {
				event.waitUntil( lh_wa_touch( STATIC_CACHENAME, request.url ) );
				return cached;
			}

			return fetch( request ).then( function( response ) {

				if ( response && response.status === 200 ) {

					var copy = response.clone();

					event.waitUntil(
						caches.open( STATIC_CACHENAME ).then( function( cache ) {
							return cache.put( request, copy );
						} ).then( function() {
							return lh_wa_record( STATIC_CACHENAME, [ request.url ] );
						} ).catch( function() {} )
					);

				}

				return response;

			} ).catch( function() {

				return Response.error();

			} );

		} )

	);

}
