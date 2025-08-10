<?php
/**
 * DragonHerd Settings Management Class
 *
 * Handles plugin settings, options, and configuration.
 *
 * @package DragonHerd
 */

namespace DragonHerd;

/**
 * Class DragonHerdSettings
 *
 * Manages all plugin settings and configuration.
 */
class DragonHerdSettings {

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'dragonherd_settings';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private const DEFAULT_SETTINGS = array(
		'bugherd_api_key'      => '',
		'openai_api_key'       => '',
		'default_project_id'   => '',
		'projects'             => array(),
		'sync_schedule'        => 'daily',
		'enable_notifications' => true,
		'notification_email'   => '',
		'custom_ai_prompt'     => '',
		'max_tasks_per_sync'   => 100,
		'cache_duration'       => 3600, // 1 hour in seconds
		'debug_mode'           => false,
	);

	/**
	 * Get all settings.
	 *
	 * @return array Settings array.
	 */
	public static function get_settings(): array {
		$settings = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( $settings, self::DEFAULT_SETTINGS );
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default_value Default value if setting not found.
	 * @return mixed Setting value.
	 */
	public static function get( string $key, $default_value = null ) {
		$settings = self::get_settings();
		return $settings[ $key ] ?? $default_value;
	}

	/**
	 * Update a specific setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool True if successful, false otherwise.
	 */
	public static function set( string $key, $value ): bool {
		$settings         = self::get_settings();
		$settings[ $key ] = $value;
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Update multiple settings at once.
	 *
	 * @param array $new_settings Array of settings to update.
	 * @return bool True if successful, false otherwise.
	 */
	public static function update( array $new_settings ): bool {
		$settings = self::get_settings();
		$settings = wp_parse_args( $new_settings, $settings );
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Delete all settings.
	 *
	 * @return bool True if successful, false otherwise.
	 */
	public static function delete(): bool {
		return delete_option( self::OPTION_NAME );
	}

	/**
	 * Get BugHerd API key.
	 *
	 * @return string API key or empty string.
	 */
	public static function get_bugherd_api_key(): string {
		return self::get( 'bugherd_api_key', '' );
	}

	/**
	 * Get OpenAI API key.
	 *
	 * @return string API key or empty string.
	 */
	public static function get_openai_api_key(): string {
		return self::get( 'openai_api_key', '' );
	}

	/**
	 * Get default project ID.
	 *
	 * @return string Project ID or empty string.
	 */
	public static function get_default_project_id(): string {
		return self::get( 'default_project_id', '' );
	}

	/**
	 * Get all configured projects.
	 *
	 * @return array Array of project configurations.
	 */
	public static function get_projects(): array {
		return self::get( 'projects', array() );
	}

	/**
	 * Add or update a project.
	 *
	 * @param array $project Project configuration.
	 * @return bool True if successful, false otherwise.
	 */
	public static function add_project( array $project ): bool {
		if ( empty( $project['id'] ) || empty( $project['name'] ) ) {
			return false;
		}

		$projects                   = self::get_projects();
		$projects[ $project['id'] ] = array(
			'id'          => sanitize_text_field( $project['id'] ),
			'name'        => sanitize_text_field( $project['name'] ),
			'description' => sanitize_textarea_field( $project['description'] ?? '' ),
			'active'      => (bool) ( $project['active'] ?? true ),
			'created_at'  => current_time( 'mysql' ),
			'updated_at'  => current_time( 'mysql' ),
		);

		return self::set( 'projects', $projects );
	}

	/**
	 * Remove a project.
	 *
	 * @param string $project_id Project ID to remove.
	 * @return bool True if successful, false otherwise.
	 */
	public static function remove_project( string $project_id ): bool {
		$projects = self::get_projects();
		if ( isset( $projects[ $project_id ] ) ) {
			unset( $projects[ $project_id ] );
			return self::set( 'projects', $projects );
		}
		return false;
	}

	/**
	 * Get sync schedule.
	 *
	 * @return string Schedule frequency.
	 */
	public static function get_sync_schedule(): string {
		return self::get( 'sync_schedule', 'daily' );
	}

	/**
	 * Get available sync schedules.
	 *
	 * @return array Array of schedule options.
	 */
	public static function get_sync_schedule_options(): array {
		return array(
			'disabled' => __( 'Disabled', 'dragonherd' ),
			'hourly'   => __( 'Every Hour', 'dragonherd' ),
			'daily'    => __( 'Daily', 'dragonherd' ),
			'weekly'   => __( 'Weekly', 'dragonherd' ),
		);
	}

	/**
	 * Export settings for backup.
	 *
	 * @return string JSON encoded settings.
	 */
	public static function export(): string {
		$settings = self::get_settings();
		// Remove sensitive data for export.
		$settings['bugherd_api_key'] = '';
		$settings['openai_api_key']  = '';

		return wp_json_encode( $settings, JSON_PRETTY_PRINT );
	}

	/**
	 * Import settings from backup.
	 *
	 * @param string $json JSON encoded settings.
	 * @return bool True if successful, false otherwise.
	 */
	public static function import( string $json ): bool {
		$imported_settings = json_decode( $json, true );

		if ( ! is_array( $imported_settings ) ) {
			return false;
		}

		// Validate and sanitize imported settings.
		$current_settings = self::get_settings();
		$valid_keys       = array_keys( self::DEFAULT_SETTINGS );

		foreach ( $imported_settings as $key => $value ) {
			if ( in_array( $key, $valid_keys, true ) ) {
				// Don't import API keys for security.
				if ( ! in_array( $key, array( 'bugherd_api_key', 'openai_api_key' ), true ) ) {
					$current_settings[ $key ] = $value;
				}
			}
		}

		return update_option( self::OPTION_NAME, $current_settings );
	}

	/**
	 * Validate settings before saving.
	 *
	 * @param array $settings Settings to validate.
	 * @return array Validated settings.
	 */
	public static function validate( array $settings ): array {
		$validated = array();

		// Validate API keys.
		if ( isset( $settings['bugherd_api_key'] ) ) {
			$validated['bugherd_api_key'] = sanitize_text_field( $settings['bugherd_api_key'] );
		}

		if ( isset( $settings['openai_api_key'] ) ) {
			$validated['openai_api_key'] = sanitize_text_field( $settings['openai_api_key'] );
		}

		// Validate project ID.
		if ( isset( $settings['default_project_id'] ) ) {
			$validated['default_project_id'] = sanitize_text_field( $settings['default_project_id'] );
		}

		// Validate sync schedule.
		if ( isset( $settings['sync_schedule'] ) ) {
			$valid_schedules = array_keys( self::get_sync_schedule_options() );
			if ( in_array( $settings['sync_schedule'], $valid_schedules, true ) ) {
				$validated['sync_schedule'] = $settings['sync_schedule'];
			}
		}

		// Validate notification settings.
		if ( isset( $settings['enable_notifications'] ) ) {
			$validated['enable_notifications'] = (bool) $settings['enable_notifications'];
		}

		if ( isset( $settings['notification_email'] ) ) {
			$email = sanitize_email( $settings['notification_email'] );
			if ( is_email( $email ) ) {
				$validated['notification_email'] = $email;
			}
		}

		// Validate numeric settings.
		if ( isset( $settings['max_tasks_per_sync'] ) ) {
			$validated['max_tasks_per_sync'] = absint( $settings['max_tasks_per_sync'] );
		}

		if ( isset( $settings['cache_duration'] ) ) {
			$validated['cache_duration'] = absint( $settings['cache_duration'] );
		}

		// Validate debug mode.
		if ( isset( $settings['debug_mode'] ) ) {
			$validated['debug_mode'] = (bool) $settings['debug_mode'];
		}

		return $validated;
	}
}
