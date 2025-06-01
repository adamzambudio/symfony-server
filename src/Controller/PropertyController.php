<?php

namespace App\Controller;

use App\Entity\Property;
use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PropertyController extends AbstractController
{
    private EntityManagerInterface $em;
    private PropertyRepository $repo;

    public function __construct(EntityManagerInterface $em, PropertyRepository $repo)
    {
        $this->em   = $em;
        $this->repo = $repo;
    }

    #[Route('/api/properties', name: 'api_properties', methods: ['GET'])]
    public function list(Request $request, PropertyRepository $propertyRepository): JsonResponse
    {
        $city = $request->query->get('city');
        $type = $request->query->get('type');
        $min = $request->query->get('min');
        $max = $request->query->get('max');

        $qb = $propertyRepository->createQueryBuilder('p');

        if ($city) {
            $qb->andWhere('LOWER(p.city) = LOWER(:city)')->setParameter('city', $city);
        }

        if ($type) {
            $qb->andWhere('LOWER(p.type) = LOWER(:type)')->setParameter('type', $type);
        }

        if ($min) {
            $qb->andWhere('p.price >= :min')->setParameter('min', (float)$min);
        }

        if ($max) {
            $qb->andWhere('p.price <= :max')->setParameter('max', (float)$max);
        }

        $properties = $qb->getQuery()->getResult();

        $data = array_map(function ($property) {
            return [
                'id' => $property->getId(),
                'title' => $property->getTitle(),
                'description' => $property->getDescription(),
                'price' => $property->getPrice(),
                'city' => $property->getCity(),
                'type' => $property->getType(),
                'cp' => $property->getCp(),
            ];
        }, $properties);

        return $this->json($data);
    }

    #[Route('/api/properties/cities', name: 'api_properties_cities', methods: ['GET'])]
    public function cities(PropertyRepository $propertyRepository): JsonResponse
    {
        $cities = $propertyRepository->createQueryBuilder('p')
            ->select('DISTINCT p.city')
            ->orderBy('p.city', 'ASC')
            ->getQuery()
            ->getScalarResult();

        $cityList = array_map(fn($c) => $c['city'], $cities);

        return $this->json($cityList);
    }

    #[Route('/api/properties/types', name: 'api_properties_types', methods: ['GET'])]
    public function types(Request $request, PropertyRepository $propertyRepository): JsonResponse
    {
        $city = $request->query->get('city');

        $qb = $propertyRepository->createQueryBuilder('p')
            ->select('DISTINCT p.type')
            ->orderBy('p.type', 'ASC');

        if ($city) {
            $qb->andWhere('p.city = :city')
                ->setParameter('city', $city);
        }

        $types = $qb->getQuery()->getScalarResult();
        $typeList = array_map(fn($t) => $t['type'], $types);

        return $this->json($typeList);
    }

    #[Route('/api/properties/prices', name: 'api_properties_prices', methods: ['GET'])]
    public function prices(Request $request, PropertyRepository $propertyRepository): JsonResponse
    {
        $city = $request->query->get('city');
        $type = $request->query->get('type');

        $qb = $propertyRepository->createQueryBuilder('p')
            ->select('MIN(p.price) as minPrice', 'MAX(p.price) as maxPrice');

        if ($city) {
            $qb->andWhere('p.city = :city')
                ->setParameter('city', $city);
        }
        if ($type) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $type);
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        return $this->json([
            'min' => $result['minPrice'] ?? 0,
            'max' => $result['maxPrice'] ?? 0,
        ]);
    }

    #[Route("/api/properties", name:"list", methods:["GET"])]
    public function listar(Request $request): JsonResponse
    {
        // Puedes añadir lógica de filtros si lo deseas (por query params: ?city=X&type=Y, etc.)
        $properties = $this->repo->findAll();

        $data = [];
        foreach ($properties as $property) {
            $data[] = [
                'id'          => $property->getId(),
                'title'       => $property->getTitle(),
                'address'     => $property->getAddress(),
                'description' => $property->getDescription(),
                'price'       => $property->getPrice(),
                'createdAt'   => $property->getCreatedAt()->format('Y-m-d H:i:s'),
                'city'        => $property->getCity(),
                'type'        => $property->getType(),
                'cp'          => $property->getCp(),
            ];
        }

        return $this->json($data);
    }

    /**
     * Mostrar una sola propiedad por ID.
     * GET /api/properties/{id}
     */
    #[Route("/api/properties/{id}", name:"show", methods:["GET"])]
    public function show(int $id): JsonResponse
    {
        $property = $this->repo->find($id);

        if (!$property) {
            return $this->json(['error' => 'Property not found'], 404);
        }

        $data = [
            'id'          => $property->getId(),
            'title'       => $property->getTitle(),
            'address'     => $property->getAddress(),
            'description' => $property->getDescription(),
            'price'       => $property->getPrice(),
            'createdAt'   => $property->getCreatedAt()->format('Y-m-d H:i:s'),
            'city'        => $property->getCity(),
            'type'        => $property->getType(),
            'cp'          => $property->getCp(),
        ];

        return $this->json($data);
    }

    /**
     * Crear una nueva propiedad.
     * Solo accesible para usuarios con rol ROLE_ADMIN.
     * POST /api/properties
     */
    #[Route("/api/properties", name:"create", methods:["POST"])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        // Validar que vengan todos los campos mínimos (a tu gusto puedes añadir validaciones más estrictas)
        if (
            empty($payload['title']) ||
            !isset($payload['address']) ||    // address puede venir vacío (nullable) pero debe existir la clave
            empty($payload['description']) ||
            !isset($payload['price']) ||
            empty($payload['city']) ||
            empty($payload['type']) ||
            !isset($payload['cp'])
        ) {
            return $this->json([
                'error' => 'Faltan campos obligatorios: title, address(null ok), description, price, city, type, cp'
            ], 400);
        }

        $property = new Property();
        $property->setTitle($payload['title']);
        $property->setAddress($payload['address']); // si address es null en JSON, lo asigna como null
        $property->setDescription($payload['description']);
        $property->setPrice((float) $payload['price']);

        // createdAt: asumiremos la fecha actual al momento de creación
        $property->setCreatedAt(new \DateTimeImmutable());

        $property->setCity($payload['city']);
        $property->setType($payload['type']);
        $property->setCp((int) $payload['cp']);

        $this->em->persist($property);
        $this->em->flush();

        return $this->json([
            'message' => 'Property creada correctamente',
            'property' => [
                'id'          => $property->getId(),
                'title'       => $property->getTitle(),
                'address'     => $property->getAddress(),
                'description' => $property->getDescription(),
                'price'       => $property->getPrice(),
                'createdAt'   => $property->getCreatedAt()->format('Y-m-d H:i:s'),
                'city'        => $property->getCity(),
                'type'        => $property->getType(),
                'cp'          => $property->getCp(),
            ]
        ], 201);
    }

    /**
     * Actualizar una propiedad existente.
     * Solo accesible para usuarios con rol ROLE_ADMIN.
     * PUT o PATCH /api/properties/{id}
     */
    #[Route("/api/properties/{id}", name:"update", methods:["PUT", "PATCH"])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request): JsonResponse
    {
        $property = $this->repo->find($id);
        if (!$property) {
            return $this->json(['error' => 'Property not found'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        if (!$payload) {
            return $this->json(['error' => 'JSON inválido'], 400);
        }

        // Para cada campo, si viene en JSON, lo actualizamos; de lo contrario lo dejamos como estaba.
        if (array_key_exists('title', $payload)) {
            $property->setTitle($payload['title']);
        }
        if (array_key_exists('address', $payload)) {
            $property->setAddress($payload['address']); // puede quedar null si viene así
        }
        if (array_key_exists('description', $payload)) {
            $property->setDescription($payload['description']);
        }
        if (array_key_exists('price', $payload)) {
            $property->setPrice((float) $payload['price']);
        }
        // No modificamos createdAt en update
        if (array_key_exists('city', $payload)) {
            $property->setCity($payload['city']);
        }
        if (array_key_exists('type', $payload)) {
            $property->setType($payload['type']);
        }
        if (array_key_exists('cp', $payload)) {
            $property->setCp((int) $payload['cp']);
        }

        $this->em->flush();

        return $this->json([
            'message' => 'Property actualizada correctamente',
            'property' => [
                'id'          => $property->getId(),
                'title'       => $property->getTitle(),
                'address'     => $property->getAddress(),
                'description' => $property->getDescription(),
                'price'       => $property->getPrice(),
                'createdAt'   => $property->getCreatedAt()->format('Y-m-d H:i:s'),
                'city'        => $property->getCity(),
                'type'        => $property->getType(),
                'cp'          => $property->getCp(),
            ]
        ]);
    }

    /**
     * Eliminar una propiedad.
     * Solo accesible para usuarios con rol ROLE_ADMIN.
     * DELETE /api/properties/{id}
     */
    #[Route("/api/properties/{id}", name:"delete", methods:["DELETE"])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $property = $this->repo->find($id);
        if (!$property) {
            return $this->json(['error' => 'Property not found'], 404);
        }

        $this->em->remove($property);
        $this->em->flush();

        return $this->json([
            'message' => 'Property eliminada correctamente'
        ], 200);
    }
}
