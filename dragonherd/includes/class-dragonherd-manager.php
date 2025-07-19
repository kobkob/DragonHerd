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
}

