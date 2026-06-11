<?php

namespace App\Controller;

use App\Service\ChatSessionManager;
use App\Service\ToolDispatcher;
use App\Service\VertexAiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ChatController extends AbstractController
{
    private ChatSessionManager $sessionManager;
    private VertexAiClient $vertexClient;
    private ToolDispatcher $toolDispatcher;

    public function __construct(
        ChatSessionManager $sessionManager,
        VertexAiClient $vertexClient,
        ToolDispatcher $toolDispatcher
    ) {
        $this->sessionManager = $sessionManager;
        $this->vertexClient = $vertexClient;
        $this->toolDispatcher = $toolDispatcher;
    }

    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $sessionId = $data['sessionId'] ?? 'default';
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return $this->json(['error' => 'Message is required'], 400);
        }

        $history = $this->sessionManager->getHistory($sessionId);
        
        $userPayload = ['role' => 'user', 'parts' => [['text' => $userMessage]]];
        $this->sessionManager->addMessage($sessionId, $userPayload);
        $history[] = $userPayload;

        $tools = [[
            'functionDeclarations' => [
                [
                    'name' => 'search_properties',
                    'description' => 'Search for real estate properties based on criteria',
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'type' => ['type' => 'STRING', 'description' => 'rent or buy'],
                            'city' => ['type' => 'STRING', 'description' => 'Location city'],
                            'price_max' => ['type' => 'NUMBER', 'description' => 'Maximum price']
                        ]
                    ]
                ]
            ]
        ]];

        try {
            $response = $this->vertexClient->generateContent($history, $tools);
            $candidate = $response['candidates'][0] ?? null;

            if (!$candidate) {
                return $this->json(['error' => 'No response from AI'], 500);
            }

            $part = $candidate['content']['parts'][0] ?? null;

            if (isset($part['functionCall'])) {
                $funcName = $part['functionCall']['name'];
                $args = $part['functionCall']['args'] ?? [];
                
                $toolResult = $this->toolDispatcher->dispatch($funcName, $args);
                
                $modelPayload = ['role' => 'model', 'parts' => [$part]];
                $this->sessionManager->addMessage($sessionId, $modelPayload);
                $history[] = $modelPayload;

                $functionPayload = [
                    'role' => 'function', 
                    'parts' => [[
                        'functionResponse' => [
                            'name' => $funcName,
                            'response' => ['name' => $funcName, 'content' => $toolResult]
                        ]
                    ]]
                ];
                $this->sessionManager->addMessage($sessionId, $functionPayload);
                $history[] = $functionPayload;

                $response = $this->vertexClient->generateContent($history, $tools);
                $candidate = $response['candidates'][0] ?? null;
                $part = $candidate['content']['parts'][0] ?? null;
            }

            if (isset($part['text'])) {
                $modelPayload = ['role' => 'model', 'parts' => [['text' => $part['text']]]];
                $this->sessionManager->addMessage($sessionId, $modelPayload);
                
                return $this->json(['response' => $part['text']]);
            }

            return $this->json(['error' => 'Unexpected response format'], 500);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
