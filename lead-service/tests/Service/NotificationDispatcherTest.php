<?php

namespace App\Tests\Service;

use App\Model\Lead;
use App\Service\NotificationDispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationDispatcherTest extends TestCase
{
    public function testDispatchLeadNotificationSendsEmail(): void
    {
        $lead = new Lead();
        $lead->name = 'John';
        $lead->email = 'john@example.com';
        $lead->phone = '123';
        $lead->propertyId = 99;

        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getSubject() === 'New Property Lead: John'
                    && str_contains($email->getTextBody(), 'john@example.com');
            }));

        $dispatcher = new NotificationDispatcher($mailerMock, 'test-agent@wolo.com');
        $dispatcher->dispatchLeadNotification($lead);
    }
}
