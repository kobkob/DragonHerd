<?php
/**
 * PHPUnit bootstrap file for DragonHerd plugin tests.
 *
 * @package DragonHerd
 */

// Composer autoloader.
require_once __DIR__ . '/../vendor/autoload.php';

// Brain Monkey setup for WordPress function mocking.
\Brain\Monkey\setUp();

// Define WordPress constants that might be used in our code.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

// Define plugin constants.
define( 'DRAGONHERD_PATH', __DIR__ . '/../dragonherd/' );
define( 'DRAGONHERD_URL', 'http://example.com/wp-content/plugins/dragonherd/' );

// WordPress functions will be mocked by Brain Monkey in individual tests.
// Don't define them here to avoid conflicts.

// Register shutdown function for Brain Monkey.
register_shutdown_function(
	function () {
		\Brain\Monkey\tearDown();
	}
);
