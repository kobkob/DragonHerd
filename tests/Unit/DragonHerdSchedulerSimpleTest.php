<?php

namespace DragonHerd\Tests\Unit;

use DragonHerd\DragonHerdScheduler;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Simplified test case for DragonHerdScheduler class.
 *
 * @covers \DragonHerd\DragonHerdScheduler
 */
class DragonHerdSchedulerSimpleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
		
		// Define WordPress constants
		if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
			define( 'MINUTE_IN_SECONDS', 60 );
		}
	}

	protected function tearDown(): void {
		Mockery::close();
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test scheduler initialization.
	 *
	 * @test
	 */
	public function it_can_initialize_scheduler(): void {
		\Brain\Monkey\Functions\expect( 'add_action' )
			->once()
			->with( 'init', Mockery::type( 'array' ) );

		\Brain\Monkey\Functions\expect( 'add_action' )
			->once()
			->with( 'dragonherd_sync_tasks', Mockery::type( 'array' ) );

		\Brain\Monkey\Functions\expect( 'add_filter' )
			->once()
			->with( 'cron_schedules', Mockery::type( 'array' ) );

		// This should not throw any exceptions
		DragonHerdScheduler::init();

		$this->assertTrue( true ); // If we get here, init worked
	}

	/**
	 * Test adding custom cron intervals.
	 *
	 * @test
	 */
	public function it_can_add_custom_cron_intervals(): void {
		\Brain\Monkey\Functions\expect( '__' )
			->twice()
			->andReturnArg( 1 );

		$existing_schedules = array(
			'hourly' => array(
				'interval' => 3600,
				'display'  => 'Hourly',
			),
		);

		$updated_schedules = DragonHerdScheduler::add_custom_cron_intervals( $existing_schedules );

		$this->assertArrayHasKey( 'fifteen_minutes', $updated_schedules );
		$this->assertArrayHasKey( 'thirty_minutes', $updated_schedules );
		$this->assertEquals( 15 * 60, $updated_schedules['fifteen_minutes']['interval'] );
		$this->assertEquals( 30 * 60, $updated_schedules['thirty_minutes']['interval'] );
	}

	/**
	 * Test setup schedules when sync is disabled.
	 *
	 * @test
	 */
	public function it_handles_disabled_sync_schedule(): void {
		// Mock getting disabled schedule
		\Brain\Monkey\Functions\expect( 'get_option' )
			->once()
			->andReturn( array( 'sync_schedule' => 'disabled' ) );

		\Brain\Monkey\Functions\expect( 'wp_parse_args' )
			->once()
			->andReturnUsing( function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			} );

		// Should check for existing scheduled event
		\Brain\Monkey\Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'dragonherd_sync_tasks' )
			->andReturn( false );

		// Should NOT clear schedule when no existing event found
		\Brain\Monkey\Functions\expect( 'wp_clear_scheduled_hook' )->never();

		// Should NOT schedule new event for disabled
		\Brain\Monkey\Functions\expect( 'wp_schedule_event' )->never();

		DragonHerdScheduler::setup_schedules();

		$this->assertTrue( true ); // If we get here, setup worked correctly
	}

	/**
	 * Test setup schedules when sync is enabled.
	 *
	 * @test
	 */
	public function it_handles_enabled_sync_schedule(): void {
		// Mock getting daily schedule
		\Brain\Monkey\Functions\expect( 'get_option' )
			->once()
			->andReturn( array( 'sync_schedule' => 'daily' ) );

		\Brain\Monkey\Functions\expect( 'wp_parse_args' )
			->once()
			->andReturnUsing( function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			} );

		// Should check for existing scheduled event (none exists) - called twice: once in if condition, once in second if condition
		\Brain\Monkey\Functions\expect( 'wp_next_scheduled' )
			->twice()
			->with( 'dragonherd_sync_tasks' )
			->andReturn( false );

		// Should NOT clear schedule when no existing event found
		\Brain\Monkey\Functions\expect( 'wp_clear_scheduled_hook' )->never();

		// Should schedule new event
		\Brain\Monkey\Functions\expect( 'wp_schedule_event' )
			->once()
			->with( Mockery::type( 'int' ), 'daily', 'dragonherd_sync_tasks' );

		DragonHerdScheduler::setup_schedules();

		$this->assertTrue( true ); // If we get here, setup worked correctly
	}

	/**
	 * Test getting next sync time.
	 *
	 * @test
	 */
	public function it_can_get_next_sync_time(): void {
		$expected_timestamp = time() + 3600; // 1 hour from now

		\Brain\Monkey\Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'dragonherd_sync_tasks' )
			->andReturn( $expected_timestamp );

		$next_sync = DragonHerdScheduler::get_next_sync_time();

		$this->assertEquals( $expected_timestamp, $next_sync );
	}

	/**
	 * Test getting next sync time when none scheduled.
	 *
	 * @test
	 */
	public function it_returns_false_when_no_sync_scheduled(): void {
		\Brain\Monkey\Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'dragonherd_sync_tasks' )
			->andReturn( false );

		$next_sync = DragonHerdScheduler::get_next_sync_time();

		$this->assertFalse( $next_sync );
	}

	/**
	 * Test that class has expected static methods.
	 *
	 * @test
	 */
	public function it_has_expected_static_methods(): void {
		$this->assertTrue( method_exists( DragonHerdScheduler::class, 'init' ) );
		$this->assertTrue( method_exists( DragonHerdScheduler::class, 'setup_schedules' ) );
		$this->assertTrue( method_exists( DragonHerdScheduler::class, 'add_custom_cron_intervals' ) );
		$this->assertTrue( method_exists( DragonHerdScheduler::class, 'run_sync' ) );
		$this->assertTrue( method_exists( DragonHerdScheduler::class, 'get_next_sync_time' ) );
	}

	/**
	 * Test that constants are defined correctly.
	 *
	 * @test
	 */
	public function it_has_required_constants(): void {
		// We can't access private constants directly, but we can test that
		// the class uses the expected hook name by checking the init method
		\Brain\Monkey\Functions\expect( 'add_action' )
			->times( 2 )
			->withArgs( function( $hook ) {
				return in_array( $hook, [ 'init', 'dragonherd_sync_tasks' ], true );
			} );

		\Brain\Monkey\Functions\expect( 'add_filter' )
			->once()
			->with( 'cron_schedules', Mockery::type( 'array' ) );

		DragonHerdScheduler::init();

		$this->assertTrue( true );
	}
}
