<?php

namespace App\Tests\MessageHandler;

use App\Message\LeadSubmitted;
use App\MessageHandler\LeadSubmittedHandler;
use App\Model\Lead;
use App\Service\NotificationDispatcher;
use PHPUnit\Framework\TestCase;

class LeadSubmittedHandlerTest extends TestCase
{
    public function testHandleDispatchesNotification(): void
    {
        $lead = new Lead();
        $lead->name = 'Test Lead';

        $message = new LeadSubmitted($lead);

        $dispatcherMock = $this->createMock(NotificationDispatcher::class);
        $dispatcherMock->expects($this->once())
            ->method('dispatchLeadNotification')
            ->with($lead);

        $handler = new LeadSubmittedHandler($dispatcherMock);
        $handler($message);
    }
}
