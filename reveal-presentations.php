<?php
/**
 * Plugin Name: Reveal JS Presentations
 * Plugin URI: http://plugins.ten-321.com/reveal-presentations/
 * Description: Allows you to easily create reveal.js presentations in WordPress
 * Version: 1.0
 * Author: Curtiss Grymala
 * Author URI: http://ten-321.com/
 * License: GPL2
 */

/**
 * Include the class definition
 */
if ( ! class_exists( 'Reveal_Presentations' ) ) {
	include_once( plugin_dir_path( __FILE__ ) . '/classes/class-reveal-presentations.php' );
}