<?php
/**
 * Plugin Name: PTA SUS Test Data Generator
 * Plugin URI:  https://github.com/
 * Description: Generates realistic test data (users, sheets, tasks, signups) for the PTA Volunteer Sign-Up Sheets plugin. For development/local use only.
 * Version:     1.0.0
 * Author:      PTA Volunteer Suite
 * Text Domain: pta-sus-test-data-generator
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PTG_VERSION', '1.0.0' );
define( 'PTG_PATH', plugin_dir_path( __FILE__ ) );
define( 'PTG_URL', plugin_dir_url( __FILE__ ) );

/**
 * Dependency check — runs on admin_notices so the user sees a clear message.
 */
function ptg_dependency_notice() {
	echo '<div class="notice notice-error"><p><strong>PTA SUS Test Data Generator:</strong> Requires the <em>PTA Volunteer Sign-Up Sheets</em> plugin to be active.</p></div>';
}

// If the core plugin's add_sheet function is not available yet (it loads on init),
// we do a looser check on plugins_loaded via a flag, then hook admin page normally.
// The tighter guard is inside each generator class.
add_action( 'plugins_loaded', 'ptg_init', 20 );

function ptg_init() {
	// Check for core plugin presence.
	if ( ! function_exists( 'pta_sus_add_sheet' ) ) {
		add_action( 'admin_notices', 'ptg_dependency_notice' );
		return;
	}

	require_once PTG_PATH . 'includes/class-ptg-tracker.php';
	require_once PTG_PATH . 'includes/class-ptg-user-generator.php';
	require_once PTG_PATH . 'includes/class-ptg-sheet-generator.php';
	require_once PTG_PATH . 'includes/class-ptg-signup-generator.php';
	require_once PTG_PATH . 'includes/class-ptg-admin.php';

	add_action( 'admin_menu',             array( 'PTG_Admin', 'register_menu' ), 99 );
	add_action( 'admin_enqueue_scripts',  array( 'PTG_Admin', 'enqueue_assets' ) );
	add_action( 'admin_post_ptg_generate_users',   array( 'PTG_Admin', 'handle_generate_users' ) );
	add_action( 'admin_post_ptg_generate_sheets',  array( 'PTG_Admin', 'handle_generate_sheets' ) );
	add_action( 'admin_post_ptg_generate_signups', array( 'PTG_Admin', 'handle_generate_signups' ) );
	add_action( 'admin_post_ptg_delete_data',      array( 'PTG_Admin', 'handle_delete_data' ) );
}
