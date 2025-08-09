<?php
/**
 * DragonHerd Manager Class
 *
 * Handles BugHerd API integration and OpenAI task summarization.
 *
 * @package DragonHerd
 */

namespace DragonHerd;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class DragonHerdManager
 *
 * Main class for managing BugHerd tasks and AI summarization.
 */
class DragonHerdManager {

	/**
	 * BugHerd API key.
	 *
	 * @var string
	 */
	private string $bugherdApiKey = 'YOUR_BUGHERD_API_KEY';

	/**
	 * OpenAI API key.
	 *
	 * @var string
	 */
	private string $openAiApiKey = 'YOUR_OPENAI_API_KEY';

	/**
	 * Users array for mapping user IDs to names.
	 *
	 * @var array
	 */
	private array $users = array(
		1 => 'Alice Johnson',
		2 => 'Bob Smith',
		3 => 'Charlie Brown',
		4 => 'Diana Prince',
	);

	/**
	 * HTTP client instance.
	 *
	 * @var Client
	 */
	private Client $httpClient;

	/**
	 * Constructor.
	 *
	 * @param Client|null $httpClient Optional HTTP client for dependency injection.
	 */
	public function __construct( ?Client $httpClient = null ) {
		$this->httpClient = $httpClient ?: new Client();
	}

	/**
	 * Set BugHerd API key.
	 *
	 * @param string $apiKey The API key.
	 * @return void
	 */
	public function setBugherdApiKey( string $apiKey ): void {
		$this->bugherdApiKey = $apiKey;
	}

	/**
	 * Set OpenAI API key.
	 *
	 * @param string $apiKey The API key.
	 * @return void
	 */
	public function setOpenAiApiKey( string $apiKey ): void {
		$this->openAiApiKey = $apiKey;
	}

	/**
	 * Run the main task summarization process.
	 *
	 * @return void
	 */
	public function run(): void {
		$tasks   = $this->getAllTasks();
		$message = $this->createMessage( $tasks );
		$summary = $this->sendMessageToOpenAI( $message );

		if ( $summary ) {
			// In a real implementation, this would save to WordPress options/database
			error_log( 'DragonHerd Summary: ' . $summary );
		}
	}

	/**
	 * Get all tasks from the default project.
	 *
	 * @return array
	 */
	private function getAllTasks(): array {
		// In a real implementation, this would get from WordPress options
		$defaultProjectId = 'default-project-id';
		return $this->getAllTasksByProject( $defaultProjectId );
	}

	/**
	 * Filter tasks by status.
	 *
	 * @param array  $tasks  Array of tasks.
	 * @param string $status Status to filter by.
	 * @return array
	 */
	private function getTasksByStatus( array $tasks, string $status ): array {
		return array_filter(
			$tasks,
			function ( $task ) use ( $status ) {
				return isset( $task['status'] ) && $task['status'] === $status;
			}
		);
	}

	/**
	 * Create a message from tasks for AI processing.
	 *
	 * @param array $tasks Array of tasks.
	 * @return string
	 */
	private function createMessage( array $tasks ): string {
		if ( empty( $tasks ) ) {
			return 'No tasks found to summarize.';
		}

		$message = "Please summarize the following tasks:\n\n";

		foreach ( $tasks as $task ) {
			$assigneeName = 'Unassigned';
			if ( ! empty( $task['assignee_ids'] ) && isset( $this->users[ $task['assignee_ids'][0] ] ) ) {
				$assigneeName = $this->users[ $task['assignee_ids'][0] ];
			}

			$message .= sprintf(
				"Task #%d: %s\nStatus: %s\nAssignee: %s\n\n",
				$task['id'] ?? 0,
				$task['description'] ?? 'No description',
				$task['status'] ?? 'Unknown',
				$assigneeName
			);
		}

		return $message;
	}

	/**
	 * Send message to OpenAI for processing.
	 *
	 * @param string $message Message to send.
	 * @return string|null
	 */
	private function sendMessageToOpenAI( string $message ): ?string {
		if ( $this->openAiApiKey === 'YOUR_OPENAI_API_KEY' ) {
			// Return mock response for development/testing
			return 'Mock AI Summary: Tasks are progressing well. Main focus areas include bug fixes and feature development.';
		}

		try {
			$response = $this->httpClient->post(
				'https://api.openai.com/v1/chat/completions',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $this->openAiApiKey,
						'Content-Type'  => 'application/json',
					),
					'json'    => array(
						'model'      => 'gpt-3.5-turbo',
						'messages'   => array(
							array(
								'role'    => 'system',
								'content' => 'You are a helpful assistant that summarizes project tasks.',
							),
							array(
								'role'    => 'user',
								'content' => $message,
							),
						),
						'max_tokens' => 500,
					),
				)
			);

			$data = json_decode( $response->getBody()->getContents(), true );
			return $data['choices'][0]['message']['content'] ?? null;
		} catch ( GuzzleException $e ) {
			error_log( 'OpenAI API Error: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Run filtered task summarization.
	 *
	 * @param mixed  $projectId Project ID.
	 * @param string $status    Status filter.
	 * @param int    $userId    User ID filter.
	 * @param string $keyword   Keyword filter.
	 * @return string
	 */
	public function runFiltered( $projectId, $status = '', $userId = 0, $keyword = '' ): string {
		$tasks = $this->getAllTasksByProject( $projectId );

		if ( $status ) {
			$tasks = $this->getTasksByStatus( $tasks, $status );
		}

		if ( $userId ) {
			$tasks = array_filter( $tasks, fn( $task ) => in_array( $userId, $task['assignee_ids'] ?? array() ) );
		}

		if ( $keyword ) {
			$tasks = array_filter( $tasks, fn( $task ) => str_contains( strtolower( $task['description'] ?? '' ), strtolower( $keyword ) ) );
		}

		$message = $this->createMessage( $tasks );
		$result  = $this->sendMessageToOpenAI( $message );
		return $result ?? 'Unable to generate summary.';
	}

	/**
	 * Get all tasks by project ID.
	 *
	 * @param mixed $projectId Project ID.
	 * @return array
	 */
	private function getAllTasksByProject( $projectId ): array {
		if ( $this->bugherdApiKey === 'YOUR_BUGHERD_API_KEY' ) {
			// Return mock data for development/testing
			return array(
				array(
					'id'           => 1,
					'description'  => 'Fix login bug',
					'status'       => 'todo',
					'assignee_ids' => array( 1 ),
				),
				array(
					'id'           => 2,
					'description'  => 'Update user interface',
					'status'       => 'in_progress',
					'assignee_ids' => array( 2 ),
				),
				array(
					'id'           => 3,
					'description'  => 'Write documentation',
					'status'       => 'done',
					'assignee_ids' => array( 3 ),
				),
			);
		}

		$tasks = array();
		$page  = 1;

		try {
			while ( true ) {
				$response = $this->httpClient->get(
					"https://www.bugherd.com/api_v2/projects/{$projectId}/tasks.json?page={$page}",
					array(
						'headers' => array(
							'Authorization' => 'Basic ' . base64_encode( $this->bugherdApiKey . ':x' ),
							'Content-Type'  => 'application/json',
						),
					)
				);

				$data = json_decode( $response->getBody()->getContents(), true );

				if ( empty( $data['tasks'] ) ) {
					break;
				}

				$tasks = array_merge( $tasks, $data['tasks'] );
				++$page;
			}
		} catch ( GuzzleException $e ) {
			error_log( 'BugHerd API Error: ' . $e->getMessage() );
			return array();
		}

		return $tasks;
	}
}
