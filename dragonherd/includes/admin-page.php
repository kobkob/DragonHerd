<?php
/**
 * Enhanced Admin page for DragonHerd plugin.
 *
 * @package DragonHerd
 */

use DragonHerd\DragonHerdSettings;
use DragonHerd\DragonHerdScheduler;
use DragonHerd\DragonHerdManager;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle form submissions.
 *
 * @return void
 */
function dragonherd_handle_form_submission(): void {
	if ( ! isset( $_POST['dragonherd_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['dragonherd_nonce'] ), 'dragonherd_settings' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle different form actions.
	if ( isset( $_POST['action'] ) ) {
		$action = sanitize_text_field( wp_unslash( $_POST['action'] ) );

		switch ( $action ) {
			case 'save_settings':
				dragonherd_save_settings();
				break;
			case 'add_project':
				dragonherd_add_project();
				break;
			case 'test_connection':
				dragonherd_test_connection();
				break;
			case 'manual_sync':
				dragonherd_manual_sync();
				break;
		}
	}
}

/**
 * Save general settings.
 * Note: Nonce verification is handled by dragonherd_handle_form_submission().
 *
 * @return void
 */
function dragonherd_save_settings(): void {
	$settings = array();

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in dragonherd_handle_form_submission().
	if ( isset( $_POST['bugherd_api_key'] ) ) {
		$settings['bugherd_api_key'] = sanitize_text_field( wp_unslash( $_POST['bugherd_api_key'] ) );
	}

	if ( isset( $_POST['openai_api_key'] ) ) {
		$settings['openai_api_key'] = sanitize_text_field( wp_unslash( $_POST['openai_api_key'] ) );
	}

	if ( isset( $_POST['default_project_id'] ) ) {
		$settings['default_project_id'] = sanitize_text_field( wp_unslash( $_POST['default_project_id'] ) );
	}

	if ( isset( $_POST['sync_schedule'] ) ) {
		$settings['sync_schedule'] = sanitize_text_field( wp_unslash( $_POST['sync_schedule'] ) );
	}

	$settings['enable_notifications'] = isset( $_POST['enable_notifications'] );

	if ( isset( $_POST['notification_email'] ) ) {
		$settings['notification_email'] = sanitize_email( wp_unslash( $_POST['notification_email'] ) );
	}

	if ( isset( $_POST['max_tasks_per_sync'] ) ) {
		$settings['max_tasks_per_sync'] = absint( $_POST['max_tasks_per_sync'] );
	}

	if ( isset( $_POST['cache_duration'] ) ) {
		$settings['cache_duration'] = absint( $_POST['cache_duration'] );
	}

	$settings['debug_mode'] = isset( $_POST['debug_mode'] );
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	$validated = DragonHerdSettings::validate( $settings );

	if ( DragonHerdSettings::update( $validated ) ) {
		// Update scheduler based on new settings.
		DragonHerdScheduler::setup_schedules();

		add_settings_error( 'dragonherd', 'settings_updated', __( 'Settings saved successfully!', 'dragonherd' ), 'updated' );
	} else {
		add_settings_error( 'dragonherd', 'settings_error', __( 'Failed to save settings.', 'dragonherd' ) );
	}
}

/**
 * Add a new project.
 * Note: Nonce verification is handled by dragonherd_handle_form_submission().
 *
 * @return void
 */
function dragonherd_add_project(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in dragonherd_handle_form_submission().
	if ( ! isset( $_POST['project_id'] ) || ! isset( $_POST['project_name'] ) ) {
		return;
	}

	$project = array(
		'id'          => sanitize_text_field( wp_unslash( $_POST['project_id'] ) ),
		'name'        => sanitize_text_field( wp_unslash( $_POST['project_name'] ) ),
		'description' => isset( $_POST['project_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['project_description'] ) ) : '',
		'active'      => isset( $_POST['project_active'] ),
	);
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( DragonHerdSettings::add_project( $project ) ) {
		add_settings_error( 'dragonherd', 'project_added', __( 'Project added successfully!', 'dragonherd' ), 'updated' );
	} else {
		add_settings_error( 'dragonherd', 'project_error', __( 'Failed to add project. Please check required fields.', 'dragonherd' ) );
	}
}

/**
 * Test API connection.
 *
 * @return void
 */
function dragonherd_test_connection(): void {
	try {
		$manager = new DragonHerdManager();
		$manager->setBugherdApiKey( DragonHerdSettings::get_bugherd_api_key() );
		$manager->setOpenAiApiKey( DragonHerdSettings::get_openai_api_key() );

		$result = $manager->runFiltered( 'test-project', 'todo' );

		add_settings_error( 'dragonherd', 'connection_success', __( 'Connection test successful! Result: ', 'dragonherd' ) . esc_html( substr( $result, 0, 100 ) . '...' ), 'updated' );
	} catch ( Exception $e ) {
		add_settings_error( 'dragonherd', 'connection_error', __( 'Connection test failed: ', 'dragonherd' ) . esc_html( $e->getMessage() ) );
	}
}

/**
 * Trigger manual sync.
 *
 * @return void
 */
function dragonherd_manual_sync(): void {
	if ( DragonHerdScheduler::trigger_manual_sync() ) {
		add_settings_error( 'dragonherd', 'sync_triggered', __( 'Manual sync triggered successfully! Check back in a few minutes for results.', 'dragonherd' ), 'updated' );
	} else {
		add_settings_error( 'dragonherd', 'sync_error', __( 'Failed to trigger manual sync.', 'dragonherd' ) );
	}
}

/**
 * Display the admin page.
 *
 * @return void
 */
function dragonherd_admin_page(): void {
	// Handle form submissions.
	dragonherd_handle_form_submission();

	$settings    = DragonHerdSettings::get_settings();
	$projects    = DragonHerdSettings::get_projects();
	$sync_status = DragonHerdScheduler::get_sync_status();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'DragonHerd Settings', 'dragonherd' ); ?></h1>
		<p><?php esc_html_e( 'Configure your BugHerd AI companion for automated task management.', 'dragonherd' ); ?></p>
		
		<?php settings_errors( 'dragonherd' ); ?>
		
		<div class="dragonherd-settings-container">
			<!-- API Configuration -->
			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'API Configuration', 'dragonherd' ); ?></h2>
				<div class="inside">
					<form method="post" action="">
						<?php wp_nonce_field( 'dragonherd_settings', 'dragonherd_nonce' ); ?>
						<input type="hidden" name="action" value="save_settings">
						
						<table class="form-table">
							<tr>
								<th scope="row"><label for="bugherd_api_key"><?php esc_html_e( 'BugHerd API Key', 'dragonherd' ); ?></label></th>
								<td>
									<input type="password" id="bugherd_api_key" name="bugherd_api_key" value="<?php echo esc_attr( $settings['bugherd_api_key'] ); ?>" class="regular-text" autocomplete="off">
									<p class="description"><?php esc_html_e( 'Your BugHerd API key for accessing project data.', 'dragonherd' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'dragonherd' ); ?></label></th>
								<td>
									<input type="password" id="openai_api_key" name="openai_api_key" value="<?php echo esc_attr( $settings['openai_api_key'] ); ?>" class="regular-text" autocomplete="off">
									<p class="description"><?php esc_html_e( 'Your OpenAI API key for AI-powered task summarization.', 'dragonherd' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="default_project_id"><?php esc_html_e( 'Default Project ID', 'dragonherd' ); ?></label></th>
								<td>
									<input type="text" id="default_project_id" name="default_project_id" value="<?php echo esc_attr( $settings['default_project_id'] ); ?>" class="regular-text">
									<p class="description"><?php esc_html_e( 'Default BugHerd project ID to use when no specific project is selected.', 'dragonherd' ); ?></p>
								</td>
							</tr>
						</table>
						
						<?php submit_button( __( 'Save API Settings', 'dragonherd' ) ); ?>
					</form>
				</div>
			</div>
			
			<!-- Project Management -->
			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Project Management', 'dragonherd' ); ?></h2>
				<div class="inside">
					<!-- Add Project Form -->
					<form method="post" action="">
						<?php wp_nonce_field( 'dragonherd_settings', 'dragonherd_nonce' ); ?>
						<input type="hidden" name="action" value="add_project">
						
						<table class="form-table">
							<tr>
								<th scope="row"><label for="project_id"><?php esc_html_e( 'Project ID', 'dragonherd' ); ?></label></th>
								<td>
									<input type="text" id="project_id" name="project_id" class="regular-text" required>
									<p class="description"><?php esc_html_e( 'BugHerd project ID (required).', 'dragonherd' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="project_name"><?php esc_html_e( 'Project Name', 'dragonherd' ); ?></label></th>
								<td>
									<input type="text" id="project_name" name="project_name" class="regular-text" required>
									<p class="description"><?php esc_html_e( 'Friendly name for this project (required).', 'dragonherd' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="project_description"><?php esc_html_e( 'Description', 'dragonherd' ); ?></label></th>
								<td>
									<textarea id="project_description" name="project_description" rows="3" class="large-text"></textarea>
									<p class="description"><?php esc_html_e( 'Optional description for this project.', 'dragonherd' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Active', 'dragonherd' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="project_active" checked>
										<?php esc_html_e( 'Include this project in automated syncs', 'dragonherd' ); ?>
									</label>
								</td>
							</tr>
						</table>
						
						<?php submit_button( __( 'Add Project', 'dragonherd' ), 'secondary' ); ?>
					</form>
					
					<!-- Projects List -->
					<?php if ( ! empty( $projects ) ) : ?>
						<h3><?php esc_html_e( 'Configured Projects', 'dragonherd' ); ?></h3>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'ID', 'dragonherd' ); ?></th>
									<th><?php esc_html_e( 'Name', 'dragonherd' ); ?></th>
									<th><?php esc_html_e( 'Description', 'dragonherd' ); ?></th>
									<th><?php esc_html_e( 'Status', 'dragonherd' ); ?></th>
									<th><?php esc_html_e( 'Created', 'dragonherd' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $projects as $project ) : ?>
									<tr>
										<td><code><?php echo esc_html( $project['id'] ); ?></code></td>
										<td><strong><?php echo esc_html( $project['name'] ); ?></strong></td>
										<td><?php echo esc_html( $project['description'] ); ?></td>
										<td>
											<?php if ( $project['active'] ) : ?>
												<span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php esc_html_e( 'Active', 'dragonherd' ); ?>
											<?php else : ?>
												<span class="dashicons dashicons-minus" style="color: orange;"></span> <?php esc_html_e( 'Inactive', 'dragonherd' ); ?>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( $project['created_at'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
			
			<!-- Scheduling Configuration -->
			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Automated Synchronization', 'dragonherd' ); ?></h2>
				<div class="inside">
					<form method="post" action="">
						<?php wp_nonce_field( 'dragonherd_settings', 'dragonherd_nonce' ); ?>
						<input type="hidden" name="action" value="save_settings">
						
						<table class="form-table">
							<tr>
								<th scope="row"><label for="sync_schedule"><?php esc_html_e( 'Sync Schedule', 'dragonherd' ); ?></label></th>
								<td>
									<select id="sync_schedule" name="sync_schedule">
										<?php foreach ( DragonHerdSettings::get_sync_schedule_options() as $key => $label ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['sync_schedule'], $key ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'How often should DragonHerd automatically sync tasks?', 'dragonherd' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Notifications', 'dragonherd' ); ?></th>
								<td>
									<fieldset>
										<label>
											<input type="checkbox" name="enable_notifications" <?php checked( $settings['enable_notifications'] ); ?>>
											<?php esc_html_e( 'Enable email notifications for sync events', 'dragonherd' ); ?>
										</label>
										<br><br>
										<label for="notification_email"><?php esc_html_e( 'Notification Email:', 'dragonherd' ); ?></label>
										<input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr( $settings['notification_email'] ); ?>" class="regular-text">
										<p class="description"><?php esc_html_e( 'Leave empty to use admin email.', 'dragonherd' ); ?></p>
									</fieldset>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="max_tasks_per_sync"><?php esc_html_e( 'Max Tasks per Sync', 'dragonherd' ); ?></label></th>
								<td>
									<input type="number" id="max_tasks_per_sync" name="max_tasks_per_sync" value="<?php echo esc_attr( $settings['max_tasks_per_sync'] ); ?>" min="10" max="1000" class="small-text">
									<p class="description"><?php esc_html_e( 'Maximum number of tasks to process in each sync operation.', 'dragonherd' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="cache_duration"><?php esc_html_e( 'Cache Duration (seconds)', 'dragonherd' ); ?></label></th>
								<td>
									<input type="number" id="cache_duration" name="cache_duration" value="<?php echo esc_attr( $settings['cache_duration'] ); ?>" min="300" max="86400" class="small-text">
									<p class="description"><?php esc_html_e( 'How long to cache API responses (300-86400 seconds).', 'dragonherd' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Debug Mode', 'dragonherd' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="debug_mode" <?php checked( $settings['debug_mode'] ); ?>>
										<?php esc_html_e( 'Enable debug logging', 'dragonherd' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Log detailed sync information for troubleshooting.', 'dragonherd' ); ?></p>
								</td>
							</tr>
						</table>
						
						<?php submit_button( __( 'Save Schedule Settings', 'dragonherd' ) ); ?>
					</form>
					
					<!-- Manual Sync -->
					<div class="dragonherd-manual-sync">
						<h3><?php esc_html_e( 'Manual Sync', 'dragonherd' ); ?></h3>
						<form method="post" action="">
							<?php wp_nonce_field( 'dragonherd_settings', 'dragonherd_nonce' ); ?>
							<input type="hidden" name="action" value="manual_sync">
							<p><?php esc_html_e( 'Trigger an immediate sync of all active projects.', 'dragonherd' ); ?></p>
							<?php submit_button( __( 'Sync Now', 'dragonherd' ), 'secondary' ); ?>
						</form>
					</div>
					
					<!-- Sync Status -->
					<div class="dragonherd-sync-status">
						<h3><?php esc_html_e( 'Sync Status', 'dragonherd' ); ?></h3>
						<table class="widefat">
							<tbody>
								<tr>
									<td><strong><?php esc_html_e( 'Schedule:', 'dragonherd' ); ?></strong></td>
									<td><?php echo esc_html( $sync_status['schedule_display'] ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Last Sync:', 'dragonherd' ); ?></strong></td>
									<td><?php echo $sync_status['last_sync'] ? esc_html( $sync_status['last_sync'] ) : esc_html__( 'Never', 'dragonherd' ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Next Sync:', 'dragonherd' ); ?></strong></td>
									<td><?php echo $sync_status['next_sync'] ? esc_html( $sync_status['next_sync'] ) : esc_html__( 'Not scheduled', 'dragonherd' ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Status:', 'dragonherd' ); ?></strong></td>
									<td>
										<?php if ( $sync_status['is_scheduled'] ) : ?>
											<span class="dashicons dashicons-clock" style="color: green;"></span> <?php esc_html_e( 'Scheduled', 'dragonherd' ); ?>
										<?php else : ?>
											<span class="dashicons dashicons-warning" style="color: orange;"></span> <?php esc_html_e( 'Not Scheduled', 'dragonherd' ); ?>
										<?php endif; ?>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			
			<!-- Connection Test -->
			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Connection Test', 'dragonherd' ); ?></h2>
				<div class="inside">
					<form method="post" action="">
						<?php wp_nonce_field( 'dragonherd_settings', 'dragonherd_nonce' ); ?>
						<input type="hidden" name="action" value="test_connection">
						<p><?php esc_html_e( 'Test your API connections to ensure everything is working properly.', 'dragonherd' ); ?></p>
						<?php submit_button( __( 'Test Connection', 'dragonherd' ), 'secondary' ); ?>
					</form>
				</div>
			</div>
		</div>
	</div>

	<style>
	.dragonherd-settings-container .postbox {
		margin-bottom: 20px;
	}
	.dragonherd-sync-status, .dragonherd-manual-sync {
		margin-top: 20px;
		padding-top: 20px;
		border-top: 1px solid #ddd;
	}
	.dragonherd-sync-status table {
		max-width: 600px;
	}
	.dragonherd-sync-status td {
		padding: 8px 12px;
	}
	.widefat.striped tbody tr:nth-child(odd) {
		background: #f9f9f9;
	}
	</style>
	<?php
}
