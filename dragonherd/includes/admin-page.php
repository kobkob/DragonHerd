<?php
/**
 * DragonHerd admin page functions.
 *
 * @package DragonHerd
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the DragonHerd dashboard widget.
 *
 * @return void
 */
function dragonherd_render_dashboard_widget(): void {
	$users = array(
		523581 => 'Monsenhor Filipo',
		535960 => 'Dan Lennon',
		536016 => 'Lucio Sá',
		536056 => 'Thiago Vieira',
	);

	$columns = array(
		'questions',
		'to do',
		'doing',
		'internal review',
		'to review',
		'feedback',
		'done',
	);

	echo '<form method="post">';
	wp_nonce_field( 'dragonherd_widget_action', 'dragonherd_widget_nonce' );

	// Project ID input.
	echo '<p><label><strong>Project ID:</strong><br>';
	echo '<input type="text" name="dragonherd_project_id" value="456410" style="width: 100%;"></label></p>';

	// User select.
	echo '<p><label><strong>Filter by User:</strong><br>';
	echo '<select name="dragonherd_user_id">';
	echo '<option value="">-- All --</option>';
	foreach ( $users as $id => $name ) {
		echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $name ) . '</option>';
	}
	echo '</select></label></p>';

	// Column select.
	echo '<p><label><strong>Task Status:</strong><br>';
	echo '<select name="dragonherd_status">';
	echo '<option value="">-- All --</option>';
	foreach ( $columns as $column ) {
		echo '<option value="' . esc_attr( $column ) . '">' . esc_html( ucfirst( $column ) ) . '</option>';
	}
	echo '</select></label></p>';

	// Keyword.
	echo '<p><label><strong>Keyword in Description:</strong><br>';
	echo '<input type="text" name="dragonherd_keyword" value="" style="width: 100%;"></label></p>';

	echo '<p><input type="submit" name="dragonherd_widget_submit" class="button button-primary" value="Run Filter &amp; Summarize"></p>';
	echo '</form>';

	if ( isset( $_POST['dragonherd_widget_submit'] ) && wp_verify_nonce( $_POST['dragonherd_widget_nonce'], 'dragonherd_widget_action' ) ) {
		$project_id = sanitize_text_field( $_POST['dragonherd_project_id'] ?? '' );
		$user_id    = intval( $_POST['dragonherd_user_id'] ?? 0 );
		$status     = sanitize_text_field( $_POST['dragonherd_status'] ?? '' );
		$keyword    = sanitize_text_field( $_POST['dragonherd_keyword'] ?? '' );

		$dragon  = new \DragonHerd\DragonHerdManager();
		$summary = $dragon->runFiltered( $project_id, $status, $user_id, $keyword );

		echo '<hr><h4>📝 AI Summary</h4>';
		echo '<div style="max-height: 300px; overflow-y: auto;">';
		echo wp_kses_post( nl2br( esc_html( $summary ) ) );
		echo '</div>';
	}
}

