<?php

namespace AMREU\UserBundle\Security;

use AMREU\UserBundle\Model\UserInterface as ModelUserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use AMREU\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    private $domain;
    private $ldapUserDn;
    private $ldapUsersFilter;
    private $ldapUsersUuid;
    private $successPath;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $ldap;
    private $flashBag;
    private $userManager;

    public function __construct(string $domain, string $ldapUserDn, string $ldapUsersFilter, string $ldapUsersUuid, string $successPath, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordHasherInterface $passwordEncoder, LdapInterface $ldap = null, UserManagerInterface $userManager)
    {
        $this->domain = $domain;
        $this->ldapUserDn = $ldapUserDn;
        $this->ldapUsersFilter = $ldapUsersFilter;
        $this->ldapUsersUuid = $ldapUsersUuid;
        $this->successPath = $successPath;
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
        return 'user_security_login_check' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    /**
     * @return mixed
     */
    public function getCredentials(Request $request): mixed
    {
        $credentials = [
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );
        $this->flashBag = $request->getSession()->getFlashBag();

        return $credentials;
    }
    
    /**
     * @return ModelUserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?ModelUserInterface
    {
        $username = $this->domain . '\\' . $credentials['username'];
        $user = null;
        try {
            $this->ldap->bind($username, $credentials['password']);
            $bindSuccessfull = true;
        } catch (ConnectionException $e) {
            $bindSuccessfull = false;
        }

        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        if ($bindSuccessfull) {
            $user = $this->updateUserFromLdap($credentials);
        } else {
            $user = $this->userManager->findUserByUsername($credentials['username']);
        }
        if (null === $user) {
            $this->flashBag->add('error', 'user_not_found');
            throw new CustomUserMessageAuthenticationException('Username could not be found.');
        }
        if (!$user->getActivated()) {
            $this->flashBag->add('error', 'user_deactivated');
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
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);
        if (null === $targetPath) {
            $targetPath = $this->urlGenerator->generate($this->successPath);
        }

        return new RedirectResponse($targetPath);
    }

    /**
     * @return string
     */
    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate('user_security_login_check');
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

    /*
     * Finds the user in the LDAP a returns a user objext
     *
     * Find the user in the ldap.
     * Then check DB for the same username.
     * If not found in DB add the user
     *
     * @return AMREU\UserBundle\Model\UserInterface
     */

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
}
