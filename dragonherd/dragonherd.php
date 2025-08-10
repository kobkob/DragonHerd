<?php
/**
 * Plugin Name: DragonHerd – AI-Powered Bug Task Summarizer
 * Description: Fetch tasks from BugHerd and summarize them using OpenAI inside your WordPress admin.
 * Version: 1.0.0
 * Author: Monsenhor Filipo
 *
 * @package DragonHerd
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DRAGONHERD_PATH', plugin_dir_path( __FILE__ ) );

require_once DRAGONHERD_PATH . 'includes/DragonHerdManager.php';
require_once DRAGONHERD_PATH . 'includes/DragonHerdSettings.php';
require_once DRAGONHERD_PATH . 'includes/DragonHerdScheduler.php';
require_once DRAGONHERD_PATH . 'includes/admin-page.php';

// Initialize scheduler on plugin load.
add_action(
	'plugins_loaded',
	function () {
		\DragonHerd\DragonHerdScheduler::init();
	}
);

// Add admin menu.
add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'DragonHerd',
			'DragonHerd',
			'manage_options',
			'dragonherd',
			'dragonherd_admin_page',
			'dashicons-analytics',
			30
		);
	}
);

/**
 * Add DragonHerd dashboard widget.
 *
 * @return void
 */
function dragonherd_add_dashboard_widget(): void {
	wp_add_dashboard_widget(
		'dragonherd_dashboard_widget',
		'🐉 DragonHerd Task Filter',
		'dragonherd_render_dashboard_widget'
	);
}
add_action( 'wp_dashboard_setup', 'dragonherd_add_dashboard_widget' );
