<?php

namespace App\Controller;

use App\Entity\Property;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PropertyController extends AbstractController
{
    #[Route('/api/properties', name: 'api_properties_create', methods: ['POST', 'OPTIONS'])]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type',
            ]);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        
        $property = new Property();
        $property->setTitle($data['title'] ?? '');
        $property->setPrice(isset($data['price']) ? (float)$data['price'] : 0.0);
        $property->setType($data['type'] ?? '');
        $property->setBedrooms(isset($data['bedrooms']) ? (int)$data['bedrooms'] : 0);
        $property->setLocation($data['location'] ?? '');

        $violations = $validator->validate($property);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()] = $v->getMessage();
            }
            return new JsonResponse(['errors' => $errors], 400, [
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $em->persist($property);
        $em->flush();

        return new JsonResponse([
            'id' => $property->getId(),
            'title' => $property->getTitle(),
            'price' => $property->getPrice(),
            'type' => $property->getType(),
            'bedrooms' => $property->getBedrooms(),
            'location' => $property->getLocation()
        ], 201, [
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    #[Route('/properties', name: 'properties_browse', methods: ['GET'])]
    public function browse(PropertyRepository $repository): Response
    {
        $properties = $repository->findAll();

        return $this->render('property/browse.html.twig', [
            'properties' => $properties,
        ]);
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'status' => 'OK',
            'service' => 'Property Catalog Service',
            'endpoints' => [
                'GET /api/properties' => 'Search properties'
            ]
        ]);
    }

    #[Route('/api/properties', name: 'api_properties_search', methods: ['GET'])]
    public function search(Request $request, PropertyRepository $repository): JsonResponse
    {
        try {
            $criteria = [];
            
            if ($request->query->has('type')) {
                $criteria['type'] = $request->query->get('type');
            }
            if ($request->query->has('city')) {
                $criteria['location'] = $request->query->get('city');
            }
            if ($request->query->has('price_max')) {
                $criteria['max_price'] = $request->query->get('price_max');
            }

            $properties = $repository->searchByCriteria($criteria);

            // Simple manual serialization for now, could use Symfony Serializer component
            $data = [];
            foreach ($properties as $property) {
                $data[] = [
                    'id' => $property->getId(),
                    'title' => $property->getTitle(),
                    'price' => $property->getPrice(),
                    'type' => $property->getType(),
                    'bedrooms' => $property->getBedrooms(),
                    'location' => $property->getLocation()
                ];
            }

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
