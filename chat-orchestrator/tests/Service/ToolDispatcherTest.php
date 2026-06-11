<?php

namespace App\Tests\Service;

use App\Service\ToolDispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ToolDispatcherTest extends TestCase
{
    public function testDispatchSearchProperties(): void
    {
        $mockResponse = new MockResponse(json_encode([
            ['id' => 1, 'title' => 'Test Property']
        ]));
        
        $httpClient = new MockHttpClient($mockResponse);
        $dispatcher = new ToolDispatcher($httpClient);
        
        $result = $dispatcher->dispatch('search_properties', ['city' => 'Madrid']);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('Test Property', $result[0]['title']);
        
        $requestUrl = $mockResponse->getRequestUrl();
        $this->assertStringContainsString('/api/properties?city=Madrid', $requestUrl);
    }
    
    public function testDispatchUnknownFunctionThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $httpClient = new MockHttpClient();
        $dispatcher = new ToolDispatcher($httpClient);
        $dispatcher->dispatch('unknown_function', []);
    }
}
