<?php

namespace App\Controller;

use App\Entity\Property;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Image;
use App\Repository\ImageRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;

class PropertyController extends AbstractController
{
    private EntityManagerInterface $em;
    private PropertyRepository $repo;
    private Filesystem $filesystem;
    private ImageRepository $imageRepo;

    public function __construct(EntityManagerInterface $em, PropertyRepository $repo, Filesystem $filesystem, ImageRepository $imageRepo,)
    {
        $this->em = $em;
        $this->repo = $repo;
        $this->filesystem = $filesystem;
        $this->imageRepo = $imageRepo;
    }

    #[Route('/api/properties', name: 'api_properties', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $city = $request->query->get('city');
        $type = $request->query->get('type');
        $min = $request->query->get('min');
        $max = $request->query->get('max');

        $qb = $this->repo->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('i');

        if ($city) {
            $qb->andWhere('LOWER(p.city) = LOWER(:city)')
                ->setParameter('city', $city);
        }

        if ($type) {
            $qb->andWhere('LOWER(p.type) = LOWER(:type)')
                ->setParameter('type', $type);
        }

        if ($min) {
            $qb->andWhere('p.price >= :min')
                ->setParameter('min', (float)$min);
        }

        if ($max) {
            $qb->andWhere('p.price <= :max')
                ->setParameter('max', (float)$max);
        }

        $properties = $qb->getQuery()->getResult();

        $data = array_map(fn($property) => $this->serializeProperty($property), $properties);

        return $this->json($data);
    }

    #[Route('/api/properties/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $property = $this->repo->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('i')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$property) {
            return $this->json(['error' => 'Property not found'], 404);
        }

        return $this->json($this->serializeProperty($property));
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
    public function types(Request $request): JsonResponse
    {
        $city = $request->query->get('city');

        $qb = $this->repo->createQueryBuilder('p')
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
    public function prices(Request $request): JsonResponse
    {
        $city = $request->query->get('city');
        $type = $request->query->get('type');

        $qb = $this->repo->createQueryBuilder('p')
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

    #[Route("/api/properties", name: "create", methods: ["POST"])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        // Para FormData, accede a los parámetros directamente desde $request->request->get()
        // No es necesario json_decode
        $title = $request->request->get('title');
        $address = $request->request->get('address');
        $description = $request->request->get('description');
        $price = $request->request->get('price');
        $city = $request->request->get('city');
        $type = $request->request->get('type');
        $cp = $request->request->get('cp');

        // Validación básica para campos obligatorios
        if (
            empty($title) ||
            !isset($address) || // La dirección puede ser nula, así que verifica con isset en lugar de empty
            empty($description) ||
            !isset($price) ||
            empty($city) ||
            empty($type) ||
            !isset($cp)
        ) {
            return $this->json([
                'error' => 'Faltan campos obligatorios: title, address (nullable), description, price, city, type, cp'
            ], 400);
        }

        $property = new Property();
        $property->setTitle($title);
        $property->setAddress($address);
        $property->setDescription($description);
        $property->setPrice((float)$price);
        $property->setCreatedAt(new \DateTimeImmutable());
        $property->setCity($city);
        $property->setType($type);
        $property->setCp((int)$cp);

        // --- Manejo de la subida de imágenes ---
        /** @var UploadedFile[] $uploadedFiles */
        // Accede a los archivos subidos usando $request->files->get('nombre_del_campo_en_el_frontend')
        // En tu caso, es 'images'
        $uploadedFiles = $request->files->get('images');

        if ($uploadedFiles instanceof UploadedFile) {
            // Si es un solo archivo, lo convertimos a un array que lo contiene
            $uploadedFiles = [$uploadedFiles];
        } elseif (!is_array($uploadedFiles)) {
            // Si no es un archivo ni un array (ej. null), lo convertimos a un array vacío
            $uploadedFiles = [];
        }

        foreach ($uploadedFiles as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $fileName = uniqid() . '.' . $file->guessExtension();
                $file->move($this->getParameter('image_directory'), $fileName);

                $image = new Image();
                $image->setUrl($fileName);
                $image->setProperty($property);
                $this->em->persist($image);
            } else {
                // Opcional: loguear por qué un archivo no es válido
                error_log('Archivo no válido o no es UploadedFile: ' . ($file ? $file->getClientOriginalName() : 'N/A'));
            }
        }

        $this->em->persist($property);
        $this->em->flush();

        return $this->json([
            'message' => 'Property creada correctamente',
            'property' => $this->serializeProperty($property)
        ], 201);
    }


    #[Route("/api/properties/{id}", name: "update", methods: ["PUT", "PATCH"])]
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

        if (array_key_exists('title', $payload)) {
            $property->setTitle($payload['title']);
        }
        if (array_key_exists('address', $payload)) {
            $property->setAddress($payload['address']);
        }
        if (array_key_exists('description', $payload)) {
            $property->setDescription($payload['description']);
        }
        if (array_key_exists('price', $payload)) {
            $property->setPrice((float)$payload['price']);
        }
        if (array_key_exists('city', $payload)) {
            $property->setCity($payload['city']);
        }
        if (array_key_exists('type', $payload)) {
            $property->setType($payload['type']);
        }
        if (array_key_exists('cp', $payload)) {
            $property->setCp((int)$payload['cp']);
        }

        $this->em->flush();

        return $this->json([
            'message' => 'Property actualizada correctamente',
            'property' => $this->serializeProperty($property)
        ]);
    }

    #[Route("/api/properties/{id}/update-with-images", name: "update_with_images", methods: ["POST"])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateWithImages(int $id, Request $request): JsonResponse
    {
        $property = $this->repo->find($id);
        if (!$property) {
            return $this->json(['error' => 'Property not found'], 404);
        }

        // Campos básicos desde formulario
        $property->setTitle($request->get('title'));
        $property->setAddress($request->get('address'));
        $property->setDescription($request->get('description'));
        $property->setPrice((float) $request->get('price'));
        $property->setCity($request->get('city'));
        $property->setType($request->get('type'));
        $property->setCp((int) $request->get('cp'));

        /** @var UploadedFile[] $uploadedFiles */
        $uploadedFiles = $request->files->all()['images'] ?? [];

        foreach ($uploadedFiles as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $fileName = uniqid() . '.' . $file->guessExtension();
                $file->move($this->getParameter('image_directory'), $fileName);

                $image = new Image();
                $image->setUrl($fileName);
                $image->setProperty($property);

                $this->em->persist($image);
            }
        }

        $this->em->flush();

        return $this->json([
            'message' => 'Property actualizada con imágenes',
            'property' => $this->serializeProperty($property)
        ]);
    }


    #[Route("/api/images/{id}", name: "delete_image", methods: ["DELETE"])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteImage(int $id): JsonResponse
    {
        $image = $this->imageRepo->find($id);

        if (!$image) {
            return $this->json(['error' => 'Image not found'], 404);
        }

        // Obtener la ruta del directorio de imágenes
        $imageDirectory = $this->getParameter('image_directory');
        $imagePath = $imageDirectory . '/' . $image->getUrl(); // Asumiendo que getUrl() te da el nombre del archivo

        // Eliminar el archivo físico
        if ($this->filesystem->exists($imagePath)) {
            try {
                $this->filesystem->remove($imagePath);
            } catch (\Exception $e) {
                // Loguear error si no se puede borrar el archivo, pero continuar para borrar de BD
                error_log('Error deleting image file: ' . $e->getMessage());
                return $this->json(['error' => 'Error al eliminar el archivo de imagen'], 500);
            }
        }

        // Eliminar la entrada de la imagen de la base de datos
        $this->em->remove($image);
        $this->em->flush();

        return $this->json(['message' => 'Imagen eliminada correctamente.'], 200);
    }

    
    #[Route("/api/properties/{id}", name: "delete", methods: ["DELETE"])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $property = $this->repo->find($id);
        if (!$property) {
            return $this->json(['error' => 'Property not found'], 404);
        }

        // Obtener la ruta del directorio de imágenes
        $imageDirectory = $this->getParameter('image_directory');

        // Eliminar las imágenes físicas asociadas a la propiedad
        foreach ($property->getImages() as $image) {
            $imagePath = $imageDirectory . '/' . $image->getUrl();
            if ($this->filesystem->exists($imagePath)) {
                $this->filesystem->remove($imagePath);
            }
            // Doctrine se encargará de eliminar la entidad Image de la BD si la relación
            // está configurada con cascade={"remove"} o orphanRemoval=true.
            // Si no, también deberías hacer $this->em->remove($image); aquí.
        }

        $this->em->remove($property);
        $this->em->flush();

        return $this->json([
            'message' => 'Property eliminada correctamente, incluyendo sus imágenes.'
        ], 200);
    }

    private function serializeProperty(Property $p): array
    {
        return [
            'id'            => $p->getId(),
            'title'         => $p->getTitle(),
            'address'       => $p->getAddress(),
            'description'   => $p->getDescription(),
            'price'         => $p->getPrice(),
            'createdAt'     => $p->getCreatedAt()?->format('Y-m-d H:i:s'),
            'city'          => $p->getCity(),
            'type'          => $p->getType(),
            'cp'            => $p->getCp(),
            'images'        => array_map(fn($img) => [
                'id' => $img->getId(), // <--- ¡Importante: Añadir el ID de la imagen!
                'url' => $this->getParameter('app.base_url') . '/img/' . $img->getUrl()
            ], $p->getImages()->toArray())
        ];
    }
}
