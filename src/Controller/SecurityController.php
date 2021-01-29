<?php

namespace AMREU\UserBundle\Controller;

use AMREU\UserBundle\Doctrine\UserManager;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SecurityController extends AbstractController
{
    private $userManager;

    public function __construct(UserManager $userManager, CsrfTokenManagerInterface $tokenManager = null)
    {
        $this->userManager = $userManager;
        $this->tokenManager = $tokenManager;
    }

    public function login(Request $request)
    {
        $csrfToken = $this->tokenManager
            ? $this->tokenManager->getToken('authenticate')->getValue()
            : null;
        $lastUsername = $request->getSession()->get(Security::LAST_USERNAME);

        return $this->render('@User/security/login.html.twig', [
            'last_username' => $lastUsername,
            'csrf_token' => $csrfToken,
        ]);
    }

    public function logout()
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
