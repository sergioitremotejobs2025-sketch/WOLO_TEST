<?php

namespace App\Service;

use App\Model\Lead;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationDispatcher
{
    private MailerInterface $mailer;
    private string $agentEmail;

    public function __construct(MailerInterface $mailer, string $agentEmail = 'agent@wolo.com')
    {
        $this->mailer = $mailer;
        $this->agentEmail = $agentEmail;
    }

    public function dispatchLeadNotification(Lead $lead): void
    {
        $email = (new Email())
            ->from('noreply@wolo.com')
            ->to($this->agentEmail)
            ->subject('New Property Lead: ' . $lead->name)
            ->text(sprintf(
                "You have a new lead!\nName: %s\nEmail: %s\nPhone: %s\nProperty ID: %d",
                $lead->name,
                $lead->email,
                $lead->phone ?? 'N/A',
                $lead->propertyId
            ));

        $this->mailer->send($email);
    }
}
