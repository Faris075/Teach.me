<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class AwsBedrockCourseAssistant
{
    public function recommend(string $prompt, array $context): array
    {
        if (! $this->isConfigured() || ! class_exists('Aws\\BedrockRuntime\\BedrockRuntimeClient')) {
            return $this->fallbackRecommendations($context);
        }

        try {
            $clientClass = 'Aws\\BedrockRuntime\\BedrockRuntimeClient';
            $client = new $clientClass([
                'version' => 'latest',
                'region' => config('services.bedrock.region'),
                'credentials' => [
                    'key' => config('services.bedrock.key'),
                    'secret' => config('services.bedrock.secret'),
                ],
            ]);

            $modelId = (string) config('services.bedrock.model_id');
            $systemPrompt = 'You are a course recommendation assistant for a social learning platform. Return only JSON with key "recommendations" as an array of up to 5 objects having title and reason.';

            $messages = [
                [
                    'role' => 'system',
                    'content' => [['text' => $systemPrompt]],
                ],
                [
                    'role' => 'user',
                    'content' => [[
                        'text' => json_encode([
                            'prompt' => $prompt,
                            'context' => $context,
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ],
            ];

            $result = $client->converse([
                'modelId' => $modelId,
                'messages' => $messages,
                'inferenceConfig' => [
                    'maxTokens' => 700,
                    'temperature' => 0.5,
                ],
            ]);

            $text = Arr::get($result, 'output.message.content.0.text', '{}');
            $decoded = json_decode((string) $text, true);

            if (! is_array($decoded) || ! isset($decoded['recommendations']) || ! is_array($decoded['recommendations'])) {
                return $this->fallbackRecommendations($context);
            }

            return array_values(array_map(static function (array $item): array {
                return [
                    'title' => (string) ($item['title'] ?? 'Suggested course'),
                    'reason' => (string) ($item['reason'] ?? 'Recommended based on your progress and interests.'),
                ];
            }, $decoded['recommendations']));
        } catch (\Throwable $e) {
            Log::warning('Bedrock recommendation fallback triggered', ['message' => $e->getMessage()]);
            return $this->fallbackRecommendations($context);
        }
    }

    public function providerLabel(): string
    {
        return $this->isConfigured() && class_exists('Aws\\BedrockRuntime\\BedrockRuntimeClient')
            ? 'aws-bedrock'
            : 'local-fallback';
    }

    private function isConfigured(): bool
    {
        return (bool) config('services.bedrock.key')
            && (bool) config('services.bedrock.secret')
            && (bool) config('services.bedrock.region')
            && (bool) config('services.bedrock.model_id');
    }

    private function fallbackRecommendations(array $context): array
    {
        $courseNames = collect($context['enrolled_courses'] ?? [])->take(3)->values();

        if ($courseNames->isEmpty()) {
            return [
                ['title' => 'Learning How to Learn', 'reason' => 'Build a strong study framework before taking advanced tracks.'],
                ['title' => 'Effective Note Taking', 'reason' => 'Improve retention and consistency across all your courses.'],
                ['title' => 'Goal-Based Study Planning', 'reason' => 'Create a weekly path to keep your completion rate high.'],
            ];
        }

        return $courseNames->map(function (string $name): array {
            return [
                'title' => $name . ' - Advanced Practice',
                'reason' => 'You are already active in this area. A deeper practice module can accelerate mastery.',
            ];
        })->values()->all();
    }
}
