<?php

namespace App\Message;

use App\Model\Lead;

class LeadSubmitted
{
    private Lead $lead;

    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }
}
