<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
public function register(
    Request $request,
    ManagerRegistry $doctrine,
    UserPasswordHasherInterface $passwordHasher,
    JWTTokenManagerInterface $jwtManager
): JsonResponse {
    $data = json_decode($request->getContent(), true);
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $plainPassword = $data['password'] ?? '';

    // Validaciones personalizadas
    if (!$name || !$email || !$plainPassword) {
        return new JsonResponse(['message' => 'Faltan datos obligatorios'], 400);
    }

    if (strlen($name) > 15) {
        return new JsonResponse(['message' => 'El nombre no puede tener más de 15 caracteres'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return new JsonResponse(['message' => 'El correo electrónico no es válido'], 400);
    }

    if (
        strlen($plainPassword) < 8 ||
        !preg_match('/[A-Z]/', $plainPassword) ||
        !preg_match('/[0-9]/', $plainPassword)
    ) {
        return new JsonResponse([
            'message' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula y un número'
        ], 400);
    }

    $entityManager = $doctrine->getManager();

    // Verificar si el email ya está en uso
    if ($entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
        return new JsonResponse(['message' => 'Email ya registrado'], 409);
    }

    $user = new User();
    $user->setName($name);
    $user->setEmail($email);
    $user->setRoles(['ROLE_USER']);
    $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

    $entityManager->persist($user);
    $entityManager->flush();

    // Generar token JWT para login automático
    $token = $jwtManager->create($user);

    return new JsonResponse(['token' => $token], 201);
}

}
