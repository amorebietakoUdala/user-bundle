<?php

namespace AMREU\UserBundle\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function login(Request $request, AuthenticationUtils $authenticationUtils)
    {
        return $this->render('@User/security/login.html.twig', [
            'last_username' => $request->getSession()->get(Security::LAST_USERNAME),
            'error' => $authenticationUtils->getLastAuthenticationError()
        ]);
    }

    public function logout()
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
