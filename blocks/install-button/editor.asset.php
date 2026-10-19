<?php
/**
 * Dependencies for the Install the App block editor script (no build step).
 *
 * @package LH_Web_Application
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
	'version'      => (string) filemtime( __DIR__ . '/editor.js' ),
);
