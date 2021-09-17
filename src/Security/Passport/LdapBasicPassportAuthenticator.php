<?php

namespace AMREU\UserBundle\Security\Passport;

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
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use AMREU\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class LdapBasicPassportAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
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

    public function supports(Request $request): bool
    {
        return null !== $request->headers->has('authorization') && 0 === strpos($request->headers->get('authorization'), 'Basic ');
    }

    public function getCredentials(Request $request)
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

    public function getUser(array $credentials): UserInterface
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

    public function authenticate(Request $request): PassportInterface
    {
        $credentials = $this->getCredentials($request);
        $user = $this->getUser($credentials);
        $passport = null;
        if ($user !== null) {
            $valid = $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
            $passport = new Passport(new UserBadge($credentials['username'], function ($userIdentifier) {
                return $this->userManager->findUserByUsername(['username' => $userIdentifier]);
            }), new PasswordCredentials($credentials['password']));
        }
        return $passport;
    }

    public function checkCredentials($credentials, UserInterface $user)
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

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => $exception->getMessage(),
        ], 401);
    }

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

    public function supportsRememberMe()
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