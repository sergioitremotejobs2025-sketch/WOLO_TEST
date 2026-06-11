<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChatWebControllerTest extends WebTestCase
{
    public function testChatWebPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/chat');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'WOLO Real Estate');
        $this->assertSelectorExists('#chat-container');
        $this->assertSelectorExists('#message-input');
        $this->assertSelectorExists('#send-button');
    }
}
