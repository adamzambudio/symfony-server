<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/redirect-by-role', name: 'redirect_by_role')]
    public function redirectAfterLogin(Security $security): Response
    {
        $user = $security->getUser();

        if (in_array('ROLE_HOST', $user->getRoles())) {
            return $this->redirectToRoute('host_dashboard');
        }

        return $this->redirectToRoute('user_dashboard');
    }

    #[Route('/user/dashboard', name: 'user_dashboard')]
    public function userDashboard(): Response
    {
        return $this->render('user/dashboard.html.twig');
    }

    #[Route('/host/dashboard', name: 'host_dashboard')]
    public function hostDashboard(): Response
    {
        return $this->render('host/dashboard.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony handles the logout automatically
    }
}
