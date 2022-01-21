<?php

namespace AMREU\UserBundle\Security;

use AMREU\UserBundle\Model\UserInterface as ModelUserInterface;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use AMREU\UserBundle\Model\UserManagerInterface;

class CertificateAuthenticator extends AbstractGuardAuthenticator
{
    private $domain;
    private $ldapUserDn;
    private $ldapUsersFilter;
    private $ldapUsersUuid;
    private $ldapUser;
    private $ldapPassword;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $ldap;
    private $userManager;

    public function __construct(string $domain, string $ldapUserDn, string $ldapUsersFilter, string $ldapUsersUuid, string $ldapUser, string $ldapPassword, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordHasherInterface $passwordEncoder, LdapInterface $ldap = null, UserManagerInterface $userManager)
    {
        $this->domain = $domain;
        $this->ldapUserDn = $ldapUserDn;
        $this->ldapUsersFilter = $ldapUsersFilter;
        $this->ldapUsersUuid = $ldapUsersUuid;
        $this->ldapUser = $ldapUser;
        $this->ldapPassword = $ldapPassword;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->ldap = $ldap;
        $this->userManager = $userManager;
    }

    /** 
     * @return bool
    */
    public function supports(Request $request): bool
    {
        return $request->server->has('SSL_CLIENT_SAN_Email_0');
    }

    public function getCredentials(Request $request): mixed
    {
        $credentials = [
            'email' => $request->server->get('SSL_CLIENT_SAN_Email_0'),
        ];

        return $credentials;
    }

    /**
     * @return ModelUserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?ModelUserInterface
    {
        $email = $credentials['email'];
        $userLdapEntry = null;
        $userLdapEntry = $this->searchByEmail($email);

        if (null === $userLdapEntry) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        } else {
            /* @var AMREU\UserBundle\Model\UserInterface $user */
            $user = $this->userManager->findUserByUsername($userLdapEntry[0]->getAttribute($this->ldapUsersUuid));
            if (null === $user) {
                $user = $this->addUser($userLdapEntry, null);
            }
        }
        if (!$user->getActivated()) {
            throw new CustomUserMessageAuthenticationException('The user has been deactivated.');
        }
        $user = $this->userManager->updateLastLogin($user);

        return $user;
    }

    /** 
     * @return bool
    */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        /* Is the cerficate is valid no need to check password */
        return true;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword($credentials): ?string
    {
        return 'certificate';
    }

    /**
     * @return Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => $exception->getMessage(),
        ], 401);
    }

    /**
     * @return Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        // Allow the request to continue
        return null;
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate('user_security_login_check');
    }

    /**
     * Called when authentication is needed, but it's not sent.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = array(
            // you might translate this message
            'message' => 'Authentication Required',
        );

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /** 
     * @return bool
    */
    public function supportsRememberMe(): bool
    {
        return false;
    }

    private function addUser($newUser, $password)
    {
        $user = $this->userManager->newUser(
            $newUser->getAttribute($this->ldapUsersUuid)[0],
            $password,
            $newUser->getAttribute('givenName')[0],
            $newUser->getAttribute('mail')[0],
            []
        );

        return $user;
    }

    public function searchByEmail(string $email)
    {
        $filledFilter = "(&(objectclass=Person)(mail=" . $email . "))";
        try {
            $this->ldap->bind($this->ldapUser, $this->ldapPassword);
            $query = $this->ldap->query($this->ldapUserDn, $filledFilter);
            $results = $query->execute()->toArray();
            return $results;
        } catch (ConnectionException $e) {
            return null;
        }
    }
}
