<?php

namespace AMREU\UserBundle\Security;

//use App\Entity\User;

use AMREU\UserBundle\Doctrine\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    private $domain;
    private $ldapUserDn;
    private $ldapUsersFilter;
    private $ldapUsersUuid;
    private $successUrl;
    private $entityManager;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $ldap;
    private $userManager;
    private $flashBag;

    public function __construct($domain, $ldapUserDn, $ldapUsersFilter, $ldapUsersUuid, $successUrl, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, LdapInterface $ldap = null, UserManager $userManager)
    {
        $this->domain = $domain;
        $this->ldapUserDn = $ldapUserDn;
        $this->ldapUsersFilter = $ldapUsersFilter;
        $this->ldapUsersUuid = $ldapUsersUuid;
        $this->successUrl = $successUrl;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->ldap = $ldap;
        $this->userManager = $userManager;
    }

    public function supports(Request $request)
    {
        return 'user_security_login_check' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
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

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $username = $this->domain.'\\'.$credentials['username'];
        try {
            $this->ldap->bind($username, $credentials['password']);
            $bindSuccessfull = true;
        } catch (ConnectionException $e) {
            $bindSuccessfull = false;
        }

        if ($bindSuccessfull) {
            $user = $this->getUserFromLdap($credentials);
        }

        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->userManager->findUserByUsername($credentials['username']);

        if (!$user) {
            $this->flashBag->add('error', 'user_not_found');
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Username could not be found.');
        }
        if (!$user->getActivated()) {
            $this->flashBag->add('error', 'user_desactivated');
            throw new CustomUserMessageAuthenticationException('The user has been deactivated.');
        }
        $user = $this->userManager->updateLastLogin($user);

        return $user;
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

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->successUrl);

        // For example : return new RedirectResponse($this->urlGenerator->generate('some_route'));
        throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate('user_security_login_check');
    }

    private function addUser($newUser, $password)
    {
//        dd($newUser);
        $user = $this->userManager->createUser(
            $newUser->getAttribute($this->ldapUsersUuid)[0],
            $this->passwordEncoder->encodePassword($user, $password),
            $newUser->getAttribute('givenName')[0],
            $newUser->getAttribute('mail')[0],
            []);

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

    private function getUserFromLdap(array $credentials)
    {
        $filledFilter = str_replace('{username}', $credentials['username'], $this->ldapUsersFilter);
        $query = $this->ldap->query($this->ldapUserDn, $filledFilter);
        $results = $query->execute()->toArray();
        $resultsDB = $this->userManager->findUserByUsername($credentials['username']);
        if (null === $resultsDB) {
            /* @var AMREU\UserBundle\Model\UserInterface $user */
            $user = $this->addUser($results[0], $credentials['password']);
        } else {
            $user = $this->updatePassword($resultsDB, $credentials['password']);
        }

        return $user;
    }
}
