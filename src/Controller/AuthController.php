<?php
// src/Controller/AuthController.php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends AbstractController
{
    private $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    /**
     * @Route("/api/login", name="api_login", methods={"POST"})
     */
    public function login(UserInterface $user): JsonResponse
    {
        // El token es generado usando el usuario autenticado
        $token = $this->jwtManager->create($user);

        // Devolver el token generado en la respuesta JSON
        return new JsonResponse(['token' => $token]);
    }
}
