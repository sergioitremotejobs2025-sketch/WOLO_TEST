<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

class ChatSessionManager
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getHistory(string $sessionId): array
    {
        $item = $this->cache->getItem('chat_session_' . $sessionId);
        if (!$item->isHit()) {
            return [];
        }
        
        return $item->get() ?? [];
    }

    public function addMessage(string $sessionId, array $message): void
    {
        $item = $this->cache->getItem('chat_session_' . $sessionId);
        $history = $item->isHit() ? $item->get() : [];
        $history[] = $message;
        
        $item->set($history);
        $item->expiresAfter(3600); // 1 hour expiration
        $this->cache->save($item);
    }

    public function clearHistory(string $sessionId): void
    {
        $this->cache->deleteItem('chat_session_' . $sessionId);
    }
}
