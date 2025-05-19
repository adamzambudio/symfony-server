<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Security;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiSecurityController extends AbstractController
{
    private $jwtManager;
    private $userProvider;

    public function __construct(JWTTokenManagerInterface $jwtManager, UserProviderInterface $userProvider)
    {
        $this->jwtManager = $jwtManager;
        $this->userProvider = $userProvider;
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Email y password son requeridos'], 400);
        }

        // Aquí debes autenticar manualmente o usar el firewall de Symfony con JWT (mejor)
        // Este ejemplo es simplificado, deberías usar guard authenticators o el bundle JWT

        // Para simplificar, vamos a buscar el usuario
        $user = $this->userProvider->loadUserByUsername($email);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Verificar contraseña
        if (!password_verify($password, $user->getPassword())) {
            return $this->json(['error' => 'Credenciales incorrectas'], 401);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'picture' => $user->getPicture(),
            ]
        ]);
    }

    #[Route('/api/redirect-by-role', name: 'api_redirect_by_role', methods: ['GET'])]
    public function redirectByRole(Security $security): JsonResponse
    {
        /** @var UserInterface|null $user */
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => 'No autenticado'], 401);
        }

        if (in_array('ROLE_HOST', $user->getRoles())) {
            return $this->json(['redirectTo' => '/host/dashboard']);
        }

        return $this->json(['redirectTo' => '/user/dashboard']);
    }
}
