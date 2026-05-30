<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiHelperController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $manualPath = base_path('docs/master-user-manual.md');
        $manualContent = is_file($manualPath) ? file_get_contents($manualPath) : '';
        $user = $request->user();

        $systemPrompt = trim(implode("\n\n", [
            'You are the Clinic Management System Helper for Zarli Min Nwe Clinic.',
            'You must answer only using the clinic manual below.',
            "You are talking to {$user->name}, who is a {$user->role}. Only suggest actions they are allowed to take based on the manual.",
            'If information is missing from the manual, clearly say it is not documented and ask staff to check with admin.',
            "Clinic Manual:\n".$manualContent,
        ]));

        $apiKey = (string) config('services.ai.key');
        $model = (string) config('services.ai.model', 'gemini-2.5-flash');
        $provider = (string) config('services.ai.provider', 'gemini');

        if ($apiKey === '') {
            return response()->json([
                'message' => 'AI service is not configured. Please set AI_API_KEY in .env.',
            ], 500);
        }

        $apiResponse = $provider === 'gemini'
            ? $this->callGemini($apiKey, $model, $systemPrompt, $validated['message'])
            : $this->callOpenAi($apiKey, $model, $systemPrompt, $validated['message']);

        if (! $apiResponse->successful()) {
            $errorMessage = data_get($apiResponse->json(), 'error.message')
                ?? 'Unable to get AI response right now.';

            return response()->json([
                'message' => $errorMessage,
            ], 502);
        }

        $payload = $apiResponse->json();
        $answer = $provider === 'gemini'
            ? data_get($payload, 'candidates.0.content.parts.0.text')
            : (data_get($payload, 'choices.0.message.content') ?? data_get($payload, 'content.0.text'));

        return response()->json([
            'message' => $answer ?? 'No response from AI service.',
        ]);
    }

    private function callGemini(string $apiKey, string $model, string $systemPrompt, string $userMessage)
    {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        return Http::acceptJson()
            ->timeout(30)
            ->post("{$endpoint}?key={$apiKey}", [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $userMessage],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                ],
            ]);
    }

    private function callOpenAi(string $apiKey, string $model, string $systemPrompt, string $userMessage)
    {
        $endpoint = (string) config('services.ai.endpoint', 'https://api.openai.com/v1/chat/completions');

        return Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(30)
            ->post($endpoint, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.2,
            ]);
    }
}
