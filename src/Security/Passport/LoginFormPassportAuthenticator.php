<?php

namespace AMREU\UserBundle\Security\Passport;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use AMREU\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class LoginFormPassportAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
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
   private $userRepository;

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
      $this->userRepository = $userManager->getRepository();
   }

   /**
    * @return string
    */
   protected function getLoginUrl(Request $request): string
   {
      return $this->urlGenerator->generate('user_security_login_check');
   }

   /**
    * Called on every request to decide if this authenticator should be
    * used for the request. Returning `false` will cause this authenticator
    * to be skipped.
    * @return bool
    */
   public function supports(Request $request): bool
   {
      return $this->urlGenerator->generate('user_security_login_check') === $request->getRequestUri()
         && $request->isMethod('POST');
   }

   /**
    * @return mixed
    */
   private function getCredentials(Request $request): mixed
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
    * @return PassportInterface
    */
   public function authenticate(Request $request): PassportInterface
   {
      $credentials = $this->getCredentials($request);
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
      $passport = new Passport(new UserBadge($credentials['username'], function ($userIdentifier) {
         return $this->userManager->findUserByUsername(['username' => $userIdentifier]);
      }), new PasswordCredentials($credentials['password']));

      $passport->addBadge(new PasswordUpgradeBadge($credentials['password'], $this->userRepository));

      return $passport;
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

   /**
    * @return Response
    */
   public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
   {
      $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
      if (null === $targetPath) {
         $targetPath = $this->urlGenerator->generate($this->successPath);
      }

      return new RedirectResponse($targetPath);
   }

   /**
    * Override to change what happens after a bad username/password is submitted.
    *
    * @return RedirectResponse
    */
   public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
   {
      if ($request->hasSession()) {
         $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
      }

      $url = $this->getLoginUrl($request);

      return new RedirectResponse($url);
   }

   public function getPassword($credentials): ?string
   {
      return ($credentials['password']);
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

   /**
    * Override to control what happens when the user hits a secure page
    * but isn't logged in yet.
    * @return Response
    */
   public function start(Request $request, AuthenticationException $authException = null): Response
   {
      $url = $this->getLoginUrl($request);

      return new RedirectResponse($url);
   }
}
