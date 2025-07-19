<?php
/**
 * Plugin Name: DragonHerd – AI-Powered Bug Task Summarizer
 * Description: Fetch tasks from BugHerd and summarize them using OpenAI inside your WordPress admin.
 * Version: 1.0.0
 * Author: Monsenhor Filipo
 */

if (!defined('ABSPATH')) exit;

define('DRAGONHERD_PATH', plugin_dir_path(__FILE__));

require_once DRAGONHERD_PATH . 'includes/class-dragonherd-manager.php';
require_once DRAGONHERD_PATH . 'includes/admin-page.php';

add_action('admin_menu', function () {
    add_menu_page(
        'DragonHerd',
        'DragonHerd',
        'manage_options',
        'dragonherd',
        'dragonherd_admin_page',
        'dashicons-analytics',
        30
    );
});

function dragonherd_admin_page() {
    echo '<div class="wrap"><h1>🐉 DragonHerd – AI Summarizer</h1>';
    echo '<form method="post"><input type="submit" name="dragonherd_run" class="button button-primary" value="Run Task Summary"></form>';

    if (isset($_POST['dragonherd_run'])) {
        $dragon = new \DragonHerd\DragonHerdManager();
        $dragon->run();
        echo '<p><strong>Summary completed and saved.</strong></p>';
    }

    echo '</div>';
}


function dragonherd_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'dragonherd_dashboard_widget',
        '🐉 DragonHerd Task Filter',
        'dragonherd_render_dashboard_widget'
    );
}
add_action('wp_dashboard_setup', 'dragonherd_add_dashboard_widget');


public function runFiltered($projectId, $status = '', $userId = 0, $keyword = ''): string
{
    $tasks = $this->getAllTasksByProject($projectId);

    if ($status) {
        $tasks = $this->getTasksByStatus($tasks, $status);
    }

    if ($userId) {
        $tasks = array_filter($tasks, fn($task) => in_array($userId, $task['assignee_ids']));
    }

    if ($keyword) {
        $tasks = array_filter($tasks, fn($task) => str_contains(strtolower($task['description']), strtolower($keyword)));
    }

    $message = $this->createMessage($tasks);
    return $this->sendMessageToOpenAI($message);
}

private function getAllTasksByProject($projectId): array
{
    $client = new Client();
    $tasks = [];
    $page = 1;

    while (true) {
        $response = $client->get("https://www.bugherd.com/api_v2/projects/{$projectId}/tasks.json?page={$page}", [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->bugherdApiKey . ':x'),
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (empty($data['tasks'])) {
            break;
        }

        $tasks = array_merge($tasks, $data['tasks']);
        $page++;
    }

    return $tasks;
}

