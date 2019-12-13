<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Include custom CSS and JS on the user profile screen. This includes editing another user, or editing your own profile.
 */
function woop_enqueue_scripts() {
	global $pagenow;
	if ( !isset($pagenow) || $pagenow !== 'user-edit.php' && $pagenow !== 'profile.php'  ) return;
	
	$assets_url = dirname( plugin_dir_url( __FILE__ ) ) . '/assets/';
	
	wp_enqueue_style( 'woop-admin', $assets_url . '/woop-admin.css', array(), RS_WOOP_VERSION );
	wp_enqueue_script( 'tablesort', $assets_url . '/tablesort-5.1.0-all.min.js', array(), '5.1.0.all' );
	wp_enqueue_script( 'woop-admin', $assets_url . '/woop-admin.js', array( 'jquery', 'tablesort' ), RS_WOOP_VERSION );
}
add_action( 'admin_enqueue_scripts', 'woop_enqueue_scripts' );