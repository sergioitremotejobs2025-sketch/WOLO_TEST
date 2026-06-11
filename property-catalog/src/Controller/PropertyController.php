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
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PropertyController extends AbstractController
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

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

        // Pull latest database state from GCS before processing
        $this->syncFromGcs();

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

        // Push updated database state to GCS
        $this->syncToGcs();

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
        // Pull latest database state from GCS
        $this->syncFromGcs();

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
        // Pull latest database state from GCS
        $this->syncFromGcs();

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

    private function syncFromGcs(): void
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return;
            }

            $url = 'https://storage.googleapis.com/storage/v1/b/iot-microservices-gcp-source-bucket/o/data.db?alt=media';
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $dbPath = $this->getParameter('kernel.project_dir') . '/var/data.db';
                file_put_contents($dbPath, $response->getContent());
            }
        } catch (\Exception $e) {
            // Silently fallback to local version
        }
    }

    private function syncToGcs(): void
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return;
            }

            $dbPath = $this->getParameter('kernel.project_dir') . '/var/data.db';
            if (!file_exists($dbPath)) {
                return;
            }

            $url = 'https://storage.googleapis.com/upload/storage/v1/b/iot-microservices-gcp-source-bucket/o?uploadType=media&name=data.db';
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => file_get_contents($dbPath)
            ]);
        } catch (\Exception $e) {
            // Silently ignore upload errors
        }
    }

    private function getAccessToken(): ?string
    {
        try {
            $url = 'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token?scopes=https://www.googleapis.com/auth/cloud-platform';
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Metadata-Flavor' => 'Google'
                ],
                'timeout' => 2 // Short timeout in case we are running locally
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return $data['access_token'] ?? null;
            }
        } catch (\Exception $e) {
            // Fallback (e.g. local test or dev environment)
        }

        return null;
    }
}

