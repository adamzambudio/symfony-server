<?php
// src/Controller/ApiController.php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiController extends AbstractController
{
    private JWTTokenManagerInterface $jwtManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ManagerRegistry $doctrine;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher,
        ManagerRegistry $doctrine
    ) {
        $this->jwtManager = $jwtManager;
        $this->passwordHasher = $passwordHasher;
        $this->doctrine = $doctrine;
    }

    /**
     * @Route("/api/login", name="api_login", methods={"POST"})
     */
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return new JsonResponse(['message' => 'Email and password are required'], 400);
        }

        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['message' => 'Invalid password'], 401);
        }

        $token = $this->jwtManager->create($user);

        return new JsonResponse(['token' => $token]);
    }
}
