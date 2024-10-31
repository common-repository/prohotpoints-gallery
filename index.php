<?php
/**
 * Plugin Name: ProHotspots Lite - Image Gallery Hotspots
 * Description: Gutenspot helps you display woocommerce products, amazon products,  products features, visual guides, image maps, interactive image charts or graphs and blog posts in an easy and interactive way.
 * Version: 1.0.0
 * Author: AA-Team
 * Author URI: http://www.aa-team.com
 * Text Domain: hotspots
 * Domain Path: /languages
 *
 * @package youtube-video-playlist
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * The full path and filename of this bootstrap file with symlinks resolved.
 *
 * @var string AATHP_BLOCK_BOOTSTRAP_FILE
 */
define( 'AATHP_BLOCK_BOOTSTRAP_FILE', __FILE__ );

/**
 * The full path to the parent directory of this bootstrap file with symlinks resolved, with trailing slash.
 *
 * @var string AATHP_BLOCK_DIR
 */
define( 'AATHP_BLOCK_DIR', dirname( AATHP_BLOCK_BOOTSTRAP_FILE ) . '/' );

/**
 * The relative path to this plugin directory, from WP_PLUGIN_DIR, with trailing slash.
 *
 * @var string AATHP_BLOCK_REL_DIR
 */
define( 'AATHP_BLOCK_REL_DIR', basename( AATHP_BLOCK_DIR ) . '/' );

/**
 * The URL of the plugin directory, with trailing slash.
 *
 * Example: https://example.local/wp-content/plugins/hcmc-custom-objects/
 *
 * @const string AATHP_BLOCK_URL
 */
define( 'AATHP_BLOCK_URL', plugins_url( '/', AATHP_BLOCK_BOOTSTRAP_FILE ) );

require dirname( __FILE__ ) . '/blocks/index.php';

// change affilate ID
add_filter( 'affID', function( $words ) {
  return "Your_AffID";
});