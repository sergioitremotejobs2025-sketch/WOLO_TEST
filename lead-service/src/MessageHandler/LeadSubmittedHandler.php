<?php

namespace App\MessageHandler;

use App\Message\LeadSubmitted;
use App\Service\NotificationDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LeadSubmittedHandler
{
    private NotificationDispatcher $dispatcher;

    public function __construct(NotificationDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function __invoke(LeadSubmitted $message)
    {
        $this->dispatcher->dispatchLeadNotification($message->getLead());
    }
}
