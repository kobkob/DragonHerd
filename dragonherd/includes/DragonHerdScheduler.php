<?php
/**
 * DragonHerd Scheduler Class
 *
 * Handles scheduled task synchronization and background processes.
 *
 * @package DragonHerd
 */

namespace DragonHerd;

/**
 * Class DragonHerdScheduler
 *
 * Manages scheduled tasks and background processes.
 */
class DragonHerdScheduler {

	/**
	 * Hook name for the sync event.
	 *
	 * @var string
	 */
	private const SYNC_HOOK = 'dragonherd_sync_tasks';

	/**
	 * Initialize scheduler.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'setup_schedules' ) );
		add_action( self::SYNC_HOOK, array( self::class, 'run_sync' ) );
		add_filter( 'cron_schedules', array( self::class, 'add_custom_cron_intervals' ) );
	}

	/**
	 * Set up scheduled tasks based on settings.
	 *
	 * @return void
	 */
	public static function setup_schedules(): void {
		$schedule = DragonHerdSettings::get_sync_schedule();

		// Clear existing schedule.
		if ( wp_next_scheduled( self::SYNC_HOOK ) ) {
			wp_clear_scheduled_hook( self::SYNC_HOOK );
		}

		// Schedule new event if not disabled.
		if ( 'disabled' !== $schedule ) {
			if ( ! wp_next_scheduled( self::SYNC_HOOK ) ) {
				wp_schedule_event( time(), $schedule, self::SYNC_HOOK );
			}
		}
	}

	/**
	 * Add custom cron intervals.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules.
	 */
	public static function add_custom_cron_intervals( array $schedules ): array {
		// Add custom intervals if not already present.
		if ( ! isset( $schedules['fifteen_minutes'] ) ) {
			$schedules['fifteen_minutes'] = array(
				'interval' => 15 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 15 Minutes', 'dragonherd' ),
			);
		}

		if ( ! isset( $schedules['thirty_minutes'] ) ) {
			$schedules['thirty_minutes'] = array(
				'interval' => 30 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 30 Minutes', 'dragonherd' ),
			);
		}

		return $schedules;
	}

	/**
	 * Run the scheduled sync process.
	 *
	 * @return void
	 */
	public static function run_sync(): void {
		// Check if API keys are configured.
		if ( empty( DragonHerdSettings::get_bugherd_api_key() ) ) {
			error_log( 'DragonHerd: BugHerd API key not configured for scheduled sync' );
			return;
		}

		try {
			$manager = new DragonHerdManager();
			$manager->setBugherdApiKey( DragonHerdSettings::get_bugherd_api_key() );
			$manager->setOpenAiApiKey( DragonHerdSettings::get_openai_api_key() );

			// Get projects to sync.
			$projects           = DragonHerdSettings::get_projects();
			$default_project_id = DragonHerdSettings::get_default_project_id();

			if ( empty( $projects ) && ! empty( $default_project_id ) ) {
				// Sync default project only.
				self::sync_project( $manager, $default_project_id );
			} else {
				// Sync all active projects.
				foreach ( $projects as $project ) {
					if ( ! empty( $project['active'] ) ) {
						self::sync_project( $manager, $project['id'] );
					}
				}
			}

			// Update last sync time.
			DragonHerdSettings::set( 'last_sync_time', current_time( 'mysql' ) );

			// Send notification if enabled.
			self::send_sync_notification( true );

		} catch ( \Exception $e ) {
			error_log( 'DragonHerd scheduled sync error: ' . $e->getMessage() );
			self::send_sync_notification( false, $e->getMessage() );
		}
	}

	/**
	 * Sync a specific project.
	 *
	 * @param DragonHerdManager $manager Manager instance.
	 * @param string            $project_id Project ID to sync.
	 * @return void
	 */
	private static function sync_project( DragonHerdManager $manager, string $project_id ): void {
		$summary = $manager->runFiltered( $project_id );

		// Store the summary.
		$sync_results                = get_option( 'dragonherd_sync_results', array() );
		$sync_results[ $project_id ] = array(
			'summary'   => $summary,
			'timestamp' => current_time( 'mysql' ),
		);

		// Keep only the last 10 sync results per project.
		if ( count( $sync_results[ $project_id ] ) > 10 ) {
			$sync_results[ $project_id ] = array_slice( $sync_results[ $project_id ], -10 );
		}

		update_option( 'dragonherd_sync_results', $sync_results );

		if ( DragonHerdSettings::get( 'debug_mode', false ) ) {
			error_log( "DragonHerd: Synced project {$project_id} - {$summary}" );
		}
	}

	/**
	 * Send sync notification email.
	 *
	 * @param bool   $success Whether sync was successful.
	 * @param string $error_message Error message if any.
	 * @return void
	 */
	private static function send_sync_notification( bool $success, string $error_message = '' ): void {
		if ( ! DragonHerdSettings::get( 'enable_notifications', false ) ) {
			return;
		}

		$email = DragonHerdSettings::get( 'notification_email', '' );
		if ( empty( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		if ( empty( $email ) ) {
			return;
		}

		$site_name = get_bloginfo( 'name' );

		if ( $success ) {
			$subject  = sprintf( '[%s] DragonHerd Sync Completed Successfully', $site_name );
			$message  = "The scheduled DragonHerd task synchronization completed successfully.\n\n";
			$message .= 'Sync completed at: ' . current_time( 'mysql' ) . "\n";
			$message .= 'Next sync: ' . wp_date( 'Y-m-d H:i:s', wp_next_scheduled( self::SYNC_HOOK ) ) . "\n";
		} else {
			$subject  = sprintf( '[%s] DragonHerd Sync Failed', $site_name );
			$message  = "The scheduled DragonHerd task synchronization failed.\n\n";
			$message .= 'Error: ' . $error_message . "\n\n";
			$message .= "Please check your API keys and settings.\n";
		}

		wp_mail( $email, $subject, $message );
	}

	/**
	 * Get next scheduled sync time.
	 *
	 * @return int|false Timestamp of next sync or false if not scheduled.
	 */
	public static function get_next_sync_time() {
		return wp_next_scheduled( self::SYNC_HOOK );
	}

	/**
	 * Get last sync time.
	 *
	 * @return string|null Last sync time or null if never synced.
	 */
	public static function get_last_sync_time(): ?string {
		return DragonHerdSettings::get( 'last_sync_time', null );
	}

	/**
	 * Trigger manual sync.
	 *
	 * @return bool True if sync started successfully.
	 */
	public static function trigger_manual_sync(): bool {
		// Schedule immediate sync.
		wp_schedule_single_event( time(), self::SYNC_HOOK );
		return true;
	}

	/**
	 * Get sync status and statistics.
	 *
	 * @return array Sync status information.
	 */
	public static function get_sync_status(): array {
		$next_sync = self::get_next_sync_time();
		$last_sync = self::get_last_sync_time();
		$schedule  = DragonHerdSettings::get_sync_schedule();

		return array(
			'schedule'         => $schedule,
			'last_sync'        => $last_sync,
			'next_sync'        => $next_sync ? wp_date( 'Y-m-d H:i:s', $next_sync ) : null,
			'is_scheduled'     => (bool) $next_sync,
			'schedule_display' => DragonHerdSettings::get_sync_schedule_options()[ $schedule ] ?? 'Unknown',
			'sync_results'     => get_option( 'dragonherd_sync_results', array() ),
		);
	}

	/**
	 * Clear all scheduled events and results.
	 *
	 * @return void
	 */
	public static function clear_all_schedules(): void {
		wp_clear_scheduled_hook( self::SYNC_HOOK );
		delete_option( 'dragonherd_sync_results' );
		DragonHerdSettings::set( 'last_sync_time', null );
	}
}
