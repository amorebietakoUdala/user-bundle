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

class LdapBasicAuthenticator extends AbstractGuardAuthenticator
{
    private $domain;
    private $ldapUserDn;
    private $ldapUsersFilter;
    private $ldapUsersUuid;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $ldap;
    private $userManager;

    public function __construct(string $domain, string $ldapUserDn, string $ldapUsersFilter, string $ldapUsersUuid, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordHasherInterface $passwordEncoder, LdapInterface $ldap = null, UserManagerInterface $userManager)
    {
        $this->domain = $domain;
        $this->ldapUserDn = $ldapUserDn;
        $this->ldapUsersFilter = $ldapUsersFilter;
        $this->ldapUsersUuid = $ldapUsersUuid;
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
        return null !== $request->headers->has('authorization') && 0 === strpos($request->headers->get('authorization'), 'Basic ');
    }

    /**
     * @return mixed
     */
    public function getCredentials(Request $request): mixed
    {
        $authorizationHeader = $request->server->get('HTTP_AUTHORIZATION');
        $rawCredentials = base64_decode(str_replace('Basic ', '', $authorizationHeader));
        $username = $user = strstr($rawCredentials, ':', true);
        $password = $user = substr(strstr($rawCredentials, ':'), 1);
        $credentials = [
            'username' => $username,
            'password' => $password,
        ];

        return $credentials;
    }

    /**
     * @return ModelUserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ModelUserInterface
    {
        $username = $this->domain . '\\' . $credentials['username'];
        $user = null;
        try {
            $this->ldap->bind($username, $credentials['password']);
            $bindSuccessfull = true;
        } catch (ConnectionException $e) {
            $bindSuccessfull = false;
        }

        if ($bindSuccessfull) {
            $user = $this->updateUserFromLdap($credentials);
        } else {
            $user = $this->userManager->findUserByUsername($credentials['username']);
        }
        if (null === $user) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
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
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
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

    /**
     * @return string
     */
    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate('user_security_login_check');
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
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

    private function updateUserFromLdap(array $credentials)
    {
        $filledFilter = str_replace('{username}', $credentials['username'], $this->ldapUsersFilter);
        $query = $this->ldap->query($this->ldapUserDn, $filledFilter);
        $results = $query->execute()->toArray();
        $dbUser = $this->userManager->findUserByUsername($credentials['username']);
        if (null === $dbUser) {
            /* @var AMREU\UserBundle\Model\UserInterface $user */
            $user = $this->addUser($results[0], $credentials['password']);
        } else {
            $user = $this->updatePassword($dbUser, $credentials['password']);
        }

        return $user;
    }

    /*
     * Updates the password of the specified user in the database.
     *
     * @param AMREU\UserBundle\Model\UserInterface $user
     * @param string $password
     *
     * @return AMREU\UserBundle\Model\UserInterface
     */

    private function updatePassword($user, $password)
    {
        return $this->userManager->updatePassword($user, $password);
    }
}
