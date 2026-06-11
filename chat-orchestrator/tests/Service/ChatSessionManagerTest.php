<?php

namespace App\Tests\Service;

use App\Service\ChatSessionManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ChatSessionManagerTest extends TestCase
{
    private ChatSessionManager $sessionManager;
    private ArrayAdapter $cache;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        // The service uses CacheInterface
        $this->sessionManager = new ChatSessionManager($this->cache);
    }

    public function testGetEmptyHistory(): void
    {
        $history = $this->sessionManager->getHistory('session-123');
        $this->assertIsArray($history);
        $this->assertEmpty($history);
    }

    public function testAddMessageAndGetHistory(): void
    {
        $message1 = ['role' => 'user', 'parts' => [['text' => 'Hi']]];
        $message2 = ['role' => 'model', 'parts' => [['text' => 'Hello! How can I help?']]];

        $this->sessionManager->addMessage('session-123', $message1);
        $this->sessionManager->addMessage('session-123', $message2);

        $history = $this->sessionManager->getHistory('session-123');
        
        $this->assertCount(2, $history);
        $this->assertSame('user', $history[0]['role']);
        $this->assertSame('Hi', $history[0]['parts'][0]['text']);
        $this->assertSame('model', $history[1]['role']);
    }

    public function testClearHistory(): void
    {
        $message = ['role' => 'user', 'parts' => [['text' => 'Hi']]];
        $this->sessionManager->addMessage('session-456', $message);
        
        $this->assertCount(1, $this->sessionManager->getHistory('session-456'));
        
        $this->sessionManager->clearHistory('session-456');
        
        $this->assertEmpty($this->sessionManager->getHistory('session-456'));
    }
}
