<?php

namespace App\Controller;

use App\Message\LeadSubmitted;
use App\Model\Lead;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LeadController extends AbstractController
{
    #[Route('/api/leads', name: 'api_lead_capture', methods: ['POST', 'OPTIONS'])]
    public function capture(
        Request $request,
        ValidatorInterface $validator,
        MessageBusInterface $bus
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type',
            ]);
        }

        $data = json_decode($request->getContent(), true);
        
        $lead = new Lead();
        $lead->name = $data['name'] ?? null;
        $lead->email = $data['email'] ?? null;
        $lead->phone = $data['phone'] ?? null;
        $lead->propertyId = isset($data['propertyId']) ? (int) $data['propertyId'] : null;

        $violations = $validator->validate($lead);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()] = $v->getMessage();
            }
            return new JsonResponse(['errors' => $errors], 400, [
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $bus->dispatch(new LeadSubmitted($lead));

        return new JsonResponse(['status' => 'success'], 201, [
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
