<?php
/*
 * Plugin Name: Move Attachments
 * Description: Move attachments between posts
 * Plugin URI: https://myrotvorets.center/
 * Version: 1.0.0
 * Author: Myrotvorets
 * Author URI: https://myrotvorets.center/
 * License: MIT
 */

// @codeCoverageIgnoreStart
if ( defined( 'ABSPATH' ) ) {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} elseif ( file_exists( ABSPATH . 'vendor/autoload.php' ) ) {
		require_once ABSPATH . 'vendor/autoload.php';
	}

	Myrotvorets\WordPress\MoveAttachments\Plugin::instance();
}
// @codeCoverageIgnoreEnd
