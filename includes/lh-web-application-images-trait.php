<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Icon and startup image sizes, and the image URL helpers shared by the manifest and the head tags.
 * Used by LH_web_application_plugin, so LH_web_application_plugin::create_apple_startup_sizes() and the rest stay callable.
 */
trait LH_web_application_images_trait {

    static function return_image_extensions(){

        return array('webp','gif','png','jpg','jpeg');

    }

    static function has_eligible_image_extension($url){

        $extension = pathinfo(wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

        if (in_array($extension, self::return_image_extensions())){

            return true;

        } else {

            return false;

        }

    }

    /**
     * @deprecated 1.34.0 Use has_eligible_image_extension().
     */
    static function has_elegible_image_extension($url){

        _deprecated_function( 'LH_web_application_plugin::has_elegible_image_extension', '1.34.0', 'LH_web_application_plugin::has_eligible_image_extension' );

        return self::has_eligible_image_extension($url);

    }

    static function return_local_host(){

         $url = wp_parse_url(get_site_url());

         return $url['host'];

     }

    static function return_cookieless_image_host(){

        $url = wp_parse_url('https://i0.wp.com/');

        if (!empty($url['host'])){

            return $url['host'];

        } else {

            return false;

        }

    }

    static function build_url(array $parts) {
        return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') .
            ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') .
            (isset($parts['user']) ? "{$parts['user']}" : '') .
            (isset($parts['pass']) ? ":{$parts['pass']}" : '') .
            (isset($parts['user']) ? '@' : '') .
            (isset($parts['host']) ? "{$parts['host']}" : '') .
            (isset($parts['port']) ? ":{$parts['port']}" : '') .
            (isset($parts['path']) ? "{$parts['path']}" : '') .
            (isset($parts['query']) ? "?{$parts['query']}" : '') .
            (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
    }

    static function maybe_return_new_image_url($src, $dimensions = null){

        if (($url = wp_parse_url($src)) && isset($url['host']) && self::has_eligible_image_extension($src) && (strpos($url['host'], self::return_local_host()) !== false)){

            $url['path'] = '/'.$url['host'].$url['path'];

            $url['host'] = str_replace( self::return_local_host() , self::return_cookieless_image_host(), $url['host'] );

            $url = self::build_url($url);

            if ((strpos(self::return_cookieless_image_host(), 'wp.com') !== false) && !empty($dimensions)){

                return add_query_arg( "resize", $dimensions[0].','.$dimensions[1] , $url );

            } else {

                return $url;

            }

        } else {

            return false;

        }

    }

    static function create_manifest_icon_sizes() {

        $icon_sizes[0] = array('height' => '16','width' => '16');
        $icon_sizes[1] = array('height' => '48','width' => '48');
        $icon_sizes[2] = array('height' => '128','width' => '128');
        $icon_sizes[3] = array('height' => '144','width' => '144');
        $icon_sizes[4] = array('height' => '192','width' => '192');
        $icon_sizes[5] = array('height' => '512','width' => '512');

        return array_reverse($icon_sizes);

    }

    static function create_apple_icon_sizes() {

        $apple_icon_sizes[0] = array('height' => '57','width' => '57');
        $apple_icon_sizes[1] = array('height' => '72', 'width' => '72');
        $apple_icon_sizes[2] = array('height' => '114', 'width' => '114');
        $apple_icon_sizes[3] = array('height' => '144', 'width' => '144');
        $apple_icon_sizes[4] = array('height' => '152', 'width' => '152');
        $apple_icon_sizes[5] = array('height' => '167', 'width' => '167');
        $apple_icon_sizes[6] = array('height' => '180', 'width' => '180');

        return $apple_icon_sizes;

    }

    static function create_apple_startup_sizes() {

        // Screen size in points (portrait) and scale of each iOS screen. iPhones are portrait only; iPads get both orientations.
        $screens = array(
            array( 320, 568, 2, false ),  // iPhone SE (1st gen)
            array( 375, 667, 2, false ),  // iPhone SE (2nd, 3rd gen), 8, 7, 6s
            array( 414, 736, 3, false ),  // iPhone 8 Plus, 7 Plus, 6s Plus
            array( 375, 812, 3, false ),  // iPhone X, XS, 11 Pro, 12 mini, 13 mini
            array( 414, 896, 2, false ),  // iPhone XR, 11
            array( 414, 896, 3, false ),  // iPhone XS Max, 11 Pro Max
            array( 390, 844, 3, false ),  // iPhone 12, 13, 14, 16e
            array( 428, 926, 3, false ),  // iPhone 12 Pro Max, 13 Pro Max, 14 Plus
            array( 393, 852, 3, false ),  // iPhone 14 Pro, 15, 15 Pro, 16
            array( 430, 932, 3, false ),  // iPhone 14 Pro Max, 15 Plus, 15 Pro Max, 16 Plus
            array( 402, 874, 3, false ),  // iPhone 16 Pro, 17, 17 Pro
            array( 420, 912, 3, false ),  // iPhone Air
            array( 440, 956, 3, false ),  // iPhone 16 Pro Max, 17 Pro Max
            array( 744, 1133, 2, true ),  // iPad mini (6th gen and later)
            array( 768, 1024, 2, true ),  // iPad 9.7", iPad mini (5th gen and earlier)
            array( 810, 1080, 2, true ),  // iPad 10.2"
            array( 834, 1112, 2, true ),  // iPad Air (3rd gen), iPad Pro 10.5"
            array( 820, 1180, 2, true ),  // iPad Air 10.9" and 11", iPad (10th gen and later)
            array( 834, 1194, 2, true ),  // iPad Pro 11" (1st to 4th gen)
            array( 834, 1210, 2, true ),  // iPad Pro 11" (M4 and later)
            array( 1024, 1366, 2, true ), // iPad Pro 12.9", iPad Air 13"
            array( 1032, 1376, 2, true ), // iPad Pro 13" (M4 and later)
        );

        $sizes = array();

        foreach ( $screens as $screen ) {

            list( $points_width, $points_height, $scale, $both_orientations ) = $screen;

            // device-width and device-height do not swap when the device turns, so only the orientation and image differ.
            $media = '(device-width: ' . $points_width . 'px) and (device-height: ' . $points_height . 'px) and (-webkit-device-pixel-ratio: ' . $scale . ')';

            $sizes[] = array( 'width' => $points_width * $scale, 'height' => $points_height * $scale, 'media' => $media . ' and (orientation: portrait)' );

            if ( $both_orientations ) {

                $sizes[] = array( 'width' => $points_height * $scale, 'height' => $points_width * $scale, 'media' => $media . ' and (orientation: landscape)' );

            }

        }

        return apply_filters( self::return_plugin_namespace() . '_apple_startup_sizes', $sizes );

    }

    // The CDN source for the startup image: the original upload when WordPress has scaled it down, so tall screens can still be cropped from it.
    static function return_startup_image_source( $id ) {

        static $sources = array();

        if ( isset( $sources[ $id ] ) ) {

            return $sources[ $id ];

        }

        $url  = wp_get_original_image_url( $id );
        $path = wp_get_original_image_path( $id );

        // Some scaled uploads have lost their original_image record, though the original is still beside the scaled copy.
        if ( $path && preg_match( '/-scaled(\.[a-z0-9]+)$/i', $path ) ) {

            $unscaled = preg_replace( '/-scaled(\.[a-z0-9]+)$/i', '$1', $path );

            if ( file_exists( $unscaled ) ) {

                $path = $unscaled;
                $url  = preg_replace( '/-scaled(\.[a-z0-9]+)$/i', '$1', $url );

            }

        }

        $size = ( $path && file_exists( $path ) ) ? wp_getimagesize( $path ) : false;

        $sources[ $id ] = array(
            'url'    => $url,
            'width'  => $size ? (int) $size[0] : 0,
            'height' => $size ? (int) $size[1] : 0,
        );

        return $sources[ $id ];

    }

    static function return_startup_image_url( $id, $width, $height ) {

        if ( empty( $id ) || ! wp_attachment_is_image( $id ) ) {

            return false;

        }

        $source = self::return_startup_image_source( $id );

        // An image CDN built on Jetpack's (such as LH Image CDN) fits an array size inside the box; ask it to crop to the exact size instead, from the original upload.
        $cropped = false;

        $crop = function( $photon_args ) use ( $width, $height, &$cropped ) {

            unset( $photon_args['fit'] );
            $photon_args['resize'] = $width . ',' . $height;
            $cropped = true;

            return $photon_args;

        };

        $use_original = function( $url ) use ( $id, $source ) {

            $attachment_url = wp_get_attachment_url( $id );

            if ( $source['url'] && $attachment_url && wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) === wp_basename( $attachment_url ) ) {

                return str_replace( wp_basename( $attachment_url ), wp_basename( $source['url'] ), $url );

            }

            return $url;

        };

        add_filter( 'jetpack_photon_image_downsize_array', $crop );
        add_filter( 'jetpack_photon_pre_image_url', $use_original, 9 );
        $image = wp_get_attachment_image_src( $id, array( $width, $height ) );
        remove_filter( 'jetpack_photon_pre_image_url', $use_original, 9 );
        remove_filter( 'jetpack_photon_image_downsize_array', $crop );

        if ( empty( $image[0] ) || ! wp_http_validate_url( $image[0] ) ) {

            return false;

        }

        if ( $cropped ) {

            // The CDN never enlarges, so a screen bigger than the original would get the wrong size, which iOS ignores.
            return ( (int) $width <= $source['width'] && (int) $height <= $source['height'] ) ? $image[0] : false;

        }

        // Served locally, iOS only uses an image of exactly the screen size.
        return ( (int) $width === (int) $image[1] && (int) $height === (int) $image[2] ) ? $image[0] : false;

    }

    static function check_image_size($id, $type, $width, $height){

        $size = array($width,$height);

        $imagedata = wp_get_attachment_image_src( $id, $size );

        if (wp_attachment_is_image($id) && !empty($imagedata[0]) && wp_http_validate_url($imagedata[0]) && ($width == $imagedata[1]) && ($height == $imagedata[2])){

            return $imagedata[0];

        } else {


            return false;

        }

    }

}
