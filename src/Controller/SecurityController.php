<?php

namespace AMREU\UserBundle\Controller;

use LogicException;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function login(Request $request, AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $session = $request->getSession();
        if (!$error && $session->has('_security.auth_error')) {
            $error = $session->get('_security.auth_error');
            $session->remove('_security.auth_error'); // Limpiar el error después de mostrarlo
        }

        return $this->render('@User/security/login.html.twig', [
            'last_username' => $request->getSession()->get('_security.last_username'),
            'error' => $error,
        ]);
    }

    public function logout()
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
