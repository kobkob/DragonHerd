<?php

namespace DragonHerd;

use GuzzleHttp\Client;

class DragonHerdManager
{
    private string $bugherdApiKey = 'YOUR_BUGHERD_API_KEY';
    private string $openAiApiKey = 'YOUR_OPENAI_API_KEY';

    private array $users = [/* same users array as before */];

    public function run()
    {
        // same as before
    }

    private function getAllTasks(): array
    {
        // same as before
    }

    private function getTasksByStatus(array $tasks, string $status): array
    {
        // same as before
    }

    private function createMessage(array $tasks): string
    {
        // same as before
    }

    private function sendMessageToOpenAI(string $message): ?string
    {
        // same as before
    }

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

}

