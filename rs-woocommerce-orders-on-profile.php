<?php
/*
Plugin Name: RS WooCommerce Orders On Profile
Description: Displays a user's WooCommerce orders on their user profile, inside a table.
Version:     1.1.4
Author:      Radley Sustaire
Author URI:  http://radleysustaire.com/
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RS_WOOP_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'RS_WOOP_VERSION', '1.1.4' );

add_action( 'plugins_loaded', 'woop_init_plugin' );

// Initialize plugin: Load plugin files
function woop_init_plugin() {
	if ( !function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 'woop_warn_no_wc' );
		return;
	}
	
	include_once( dirname( __FILE__ ) . '/includes/enqueue.php' );
	include_once( dirname( __FILE__ ) . '/includes/user-profile.php' );
}

// Require WooCommerce
function woop_warn_no_wc() {
	?>
	<div class="error">
		<p><strong>RS WooCommerce Orders On Profile:</strong> This plugin requires WooCommerce in order to operate. Please install and activate WooCommerce, or disable this plugin.</p>
	</div>
	<?php
}