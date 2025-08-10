<?php

namespace DragonHerd\Tests\Unit;

use DragonHerd\DragonHerdSettings;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Simplified test case for DragonHerdSettings class.
 *
 * @covers \DragonHerd\DragonHerdSettings
 */
class DragonHerdSettingsSimpleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
	}

	protected function tearDown(): void {
		Mockery::close();
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test getting settings with defaults.
	 *
	 * @test
	 */
	public function it_can_get_settings_with_defaults(): void {
		// Mock WordPress functions
		\Brain\Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'dragonherd_settings', array() )
			->andReturn( array( 'bugherd_api_key' => 'test-key' ) );

		\Brain\Monkey\Functions\expect( 'wp_parse_args' )
			->once()
			->andReturnUsing( function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			} );

		$settings = DragonHerdSettings::get_settings();

		$this->assertIsArray( $settings );
		$this->assertArrayHasKey( 'bugherd_api_key', $settings );
		$this->assertEquals( 'test-key', $settings['bugherd_api_key'] );
	}

	/**
	 * Test getting specific setting value.
	 *
	 * @test
	 */
	public function it_can_get_specific_setting(): void {
		\Brain\Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'dragonherd_settings', array() )
			->andReturn( array( 'bugherd_api_key' => 'test-key-123' ) );

		\Brain\Monkey\Functions\expect( 'wp_parse_args' )
			->once()
			->andReturnUsing( function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			} );

		$api_key = DragonHerdSettings::get( 'bugherd_api_key' );
		$this->assertEquals( 'test-key-123', $api_key );
	}

	/**
	 * Test setting specific value.
	 *
	 * @test
	 */
	public function it_can_set_specific_setting(): void {
		\Brain\Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'dragonherd_settings', array() )
			->andReturn( array() );

		\Brain\Monkey\Functions\expect( 'wp_parse_args' )
			->once()
			->andReturnUsing( function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			} );

		\Brain\Monkey\Functions\expect( 'update_option' )
			->once()
			->with( 'dragonherd_settings', Mockery::type( 'array' ) )
			->andReturn( true );

		$result = DragonHerdSettings::set( 'bugherd_api_key', 'new-key' );
		$this->assertTrue( $result );
	}

	/**
	 * Test updating multiple settings.
	 *
	 * @test
	 */
	public function it_can_update_multiple_settings(): void {
		\Brain\Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'dragonherd_settings', array() )
			->andReturn( array() );

		\Brain\Monkey\Functions\expect( 'wp_parse_args' )
			->twice()
			->andReturnUsing( function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			} );

		\Brain\Monkey\Functions\expect( 'update_option' )
			->once()
			->with( 'dragonherd_settings', Mockery::type( 'array' ) )
			->andReturn( true );

		$new_settings = array(
			'bugherd_api_key' => 'new-key',
			'sync_schedule'   => 'hourly',
		);

		$result = DragonHerdSettings::update( $new_settings );
		$this->assertTrue( $result );
	}

	/**
	 * Test deleting all settings.
	 *
	 * @test
	 */
	public function it_can_delete_all_settings(): void {
		\Brain\Monkey\Functions\expect( 'delete_option' )
			->once()
			->with( 'dragonherd_settings' )
			->andReturn( true );

		$result = DragonHerdSettings::delete();
		$this->assertTrue( $result );
	}

	/**
	 * Test getting BugHerd API key.
	 *
	 * @test
	 */
	public function it_can_get_bugherd_api_key(): void {
		\Brain\Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'dragonherd_settings', array() )
			->andReturn( array( 'bugherd_api_key' => 'test-bugherd-key' ) );

		\Brain\Monkey\Functions\expect( 'wp_parse_args' )
			->once()
			->andReturnUsing( function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			} );

		$api_key = DragonHerdSettings::get_bugherd_api_key();
		$this->assertEquals( 'test-bugherd-key', $api_key );
	}

	/**
	 * Test getting OpenAI API key.
	 *
	 * @test
	 */
	public function it_can_get_openai_api_key(): void {
		\Brain\Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'dragonherd_settings', array() )
			->andReturn( array( 'openai_api_key' => 'test-openai-key' ) );

		\Brain\Monkey\Functions\expect( 'wp_parse_args' )
			->once()
			->andReturnUsing( function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			} );

		$api_key = DragonHerdSettings::get_openai_api_key();
		$this->assertEquals( 'test-openai-key', $api_key );
	}

	/**
	 * Test getting projects.
	 *
	 * @test
	 */
	public function it_can_get_projects(): void {
		$test_projects = array(
			'project-1' => array( 'id' => 'project-1', 'name' => 'Test Project' ),
		);

		\Brain\Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'dragonherd_settings', array() )
			->andReturn( array( 'projects' => $test_projects ) );

		\Brain\Monkey\Functions\expect( 'wp_parse_args' )
			->once()
			->andReturnUsing( function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			} );

		$projects = DragonHerdSettings::get_projects();
		$this->assertEquals( $test_projects, $projects );
	}

	/**
	 * Test adding a project.
	 *
	 * @test
	 */
	public function it_can_add_project(): void {
		// Mock current projects (empty)
		\Brain\Monkey\Functions\expect( 'get_option' )
			->twice() // Called in get_projects() and set()
			->with( 'dragonherd_settings', array() )
			->andReturn( array( 'projects' => array() ) );

		\Brain\Monkey\Functions\expect( 'wp_parse_args' )
			->twice()
			->andReturnUsing( function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			} );

		// Mock sanitization functions
		\Brain\Monkey\Functions\expect( 'sanitize_text_field' )
			->twice()
			->andReturnUsing( function( $input ) {
				return $input;
			} );

		\Brain\Monkey\Functions\expect( 'sanitize_textarea_field' )
			->once()
			->andReturnUsing( function( $input ) {
				return $input !== null ? $input : '';
			} );

		\Brain\Monkey\Functions\expect( 'current_time' )
			->twice()
			->with( 'mysql' )
			->andReturn( '2023-01-01 12:00:00' );

		\Brain\Monkey\Functions\expect( 'update_option' )
			->once()
			->with( 'dragonherd_settings', Mockery::type( 'array' ) )
			->andReturn( true );

		$project = array(
			'id'   => 'project-123',
			'name' => 'Test Project',
		);

		$result = DragonHerdSettings::add_project( $project );
		$this->assertTrue( $result );
	}

	/**
	 * Test removing a project.
	 *
	 * @test
	 */
	public function it_can_remove_project(): void {
		$existing_projects = array(
			'project-123' => array( 'id' => 'project-123', 'name' => 'Test Project' ),
			'project-456' => array( 'id' => 'project-456', 'name' => 'Another Project' ),
		);

		// Mock current projects
		\Brain\Monkey\Functions\expect( 'get_option' )
			->twice() // Called in get_projects() and set()
			->with( 'dragonherd_settings', array() )
			->andReturn( array( 'projects' => $existing_projects ) );

		\Brain\Monkey\Functions\expect( 'wp_parse_args' )
			->twice()
			->andReturnUsing( function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			} );

		\Brain\Monkey\Functions\expect( 'update_option' )
			->once()
			->with( 'dragonherd_settings', Mockery::type( 'array' ) )
			->andReturn( true );

		$result = DragonHerdSettings::remove_project( 'project-123' );
		$this->assertTrue( $result );
	}

	/**
	 * Test getting sync schedule options.
	 *
	 * @test
	 */
	public function it_can_get_sync_schedule_options(): void {
		\Brain\Monkey\Functions\expect( '__' )
			->times( 4 )
			->andReturnArg( 1 );

		$options = DragonHerdSettings::get_sync_schedule_options();

		$this->assertIsArray( $options );
		$this->assertArrayHasKey( 'disabled', $options );
		$this->assertArrayHasKey( 'hourly', $options );
		$this->assertArrayHasKey( 'daily', $options );
		$this->assertArrayHasKey( 'weekly', $options );
	}
}
