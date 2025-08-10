<?php
/**
 * Integration tests for DragonHerd plugin.
 *
 * @package DragonHerd
 */

namespace DragonHerd\Tests\Integration;

use PHPUnit\Framework\TestCase;
use DragonHerd\DragonHerdManager;

/**
 * Integration tests for DragonHerd plugin.
 *
 * @covers \DragonHerd
 */
class PluginTest extends TestCase {

	/**
	 * Test that plugin constants are defined correctly.
	 *
	 * @test
	 */
	public function it_defines_plugin_constants(): void {
		$this->assertTrue( defined( 'DRAGONHERD_PATH' ) );
		$this->assertIsString( DRAGONHERD_PATH );
		$this->assertNotEmpty( DRAGONHERD_PATH );
	}

	/**
	 * Test that plugin files exist.
	 *
	 * @test
	 */
	public function it_has_required_plugin_files(): void {
		$this->assertFileExists( DRAGONHERD_PATH . 'dragonherd.php' );
		$this->assertFileExists( DRAGONHERD_PATH . 'includes/DragonHerdManager.php' );
		$this->assertFileExists( DRAGONHERD_PATH . 'includes/admin-page.php' );
	}

	/**
	 * Test that class files can be required without errors.
	 *
	 * @test
	 */
	public function it_loads_required_classes(): void {
		$this->assertFileExists( DRAGONHERD_PATH . 'includes/DragonHerdManager.php' );

		// Should be able to require without errors.
		require_once DRAGONHERD_PATH . 'includes/DragonHerdManager.php';

		$this->assertTrue( class_exists( 'DragonHerd\\DragonHerdManager' ) );
	}

	/**
	 * Test plugin activation and deactivation hooks (placeholder).
	 *
	 * @test
	 */
	public function it_handles_activation_and_deactivation(): void {
		// This is a placeholder test for future activation/deactivation hooks.
		$this->assertTrue( true, 'Activation/deactivation hooks not yet implemented' );
	}
}
