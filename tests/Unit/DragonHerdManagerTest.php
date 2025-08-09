<?php

namespace DragonHerd\Tests\Unit;

use DragonHerd\DragonHerdManager;
use PHPUnit\Framework\TestCase;
use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

/**
 * Test case for DragonHerdManager class.
 *
 * @covers \DragonHerd\DragonHerdManager
 */
class DragonHerdManagerTest extends TestCase {

	private DragonHerdManager $manager;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();

		$this->manager = new DragonHerdManager();
	}

	protected function tearDown(): void {
		Mockery::close();
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test that DragonHerdManager can be instantiated.
	 *
	 * @test
	 */
	public function it_can_be_instantiated(): void {
		$this->assertInstanceOf( DragonHerdManager::class, $this->manager );
	}

	/**
	 * Test runFiltered method returns string.
	 *
	 * @test
	 */
	public function it_can_run_filtered_by_status(): void {
		// Use default mock data (API keys are set to default values)
		$result = $this->manager->runFiltered( 'test-project', 'todo' );

		// Should return a string result
		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	/**
	 * Test that API keys can be set (if we add setters).
	 *
	 * @test
	 */
	public function it_has_required_properties(): void {
		$reflection = new \ReflectionClass( $this->manager );

		$this->assertTrue( $reflection->hasProperty( 'bugherdApiKey' ) );
		$this->assertTrue( $reflection->hasProperty( 'openAiApiKey' ) );
	}

	/**
	 * Test task filtering by status.
	 *
	 * @test
	 */
	public function it_can_filter_tasks_by_status(): void {
		$reflection = new \ReflectionClass( $this->manager );
		$method     = $reflection->getMethod( 'getTasksByStatus' );
		$method->setAccessible( true );

		$tasks = array(
			array(
				'id'     => 1,
				'status' => 'todo',
			),
			array(
				'id'     => 2,
				'status' => 'done',
			),
			array(
				'id'     => 3,
				'status' => 'todo',
			),
		);

		$todoTasks = $method->invoke( $this->manager, $tasks, 'todo' );

		$this->assertCount( 2, $todoTasks );
		$this->assertEquals( 'todo', $todoTasks[0]['status'] );
		$this->assertEquals( 'todo', $todoTasks[2]['status'] );
	}

	/**
	 * Test message creation from tasks.
	 *
	 * @test
	 */
	public function it_can_create_message_from_tasks(): void {
		$reflection = new \ReflectionClass( $this->manager );
		$method     = $reflection->getMethod( 'createMessage' );
		$method->setAccessible( true );

		$tasks = array(
			array(
				'id'           => 1,
				'description'  => 'Fix login bug',
				'status'       => 'todo',
				'assignee_ids' => array( 1 ),
			),
		);

		$message = $method->invoke( $this->manager, $tasks );

		$this->assertIsString( $message );
		$this->assertStringContainsString( 'Fix login bug', $message );
	}

	/**
	 * Test that required methods exist.
	 *
	 * @test
	 */
	public function it_has_required_methods(): void {
		$this->assertTrue( method_exists( $this->manager, 'run' ) );
		$this->assertTrue( method_exists( $this->manager, 'runFiltered' ) );
	}
}
