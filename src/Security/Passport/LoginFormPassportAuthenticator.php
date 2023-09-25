<?php

namespace AMREU\UserBundle\Security\Passport;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use AMREU\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
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
   private $userManager;
   private $userRepository;
   private $internetDomain;

   public function __construct(string $domain, string $ldapUserDn, string $ldapUsersFilter, string $ldapUsersUuid, string $successPath, string $internetDomain, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordHasherInterface $passwordEncoder, LdapInterface $ldap = null, UserManagerInterface $userManager)
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
      $this->internetDomain = $internetDomain;
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

   private function getCredentials(Request $request)
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
      return $credentials;
   }

   /**
    * @return Passport
    */
   public function authenticate(Request $request): Passport
   {
      $credentials = $this->getCredentials($request);
      $username = $credentials['username'];
      // Remove Internet Domain y email is provided instead off username alone
      if ( strpos($username,$this->internetDomain) > 0 ) {
         $username = substr($username, 0, strpos($username,$this->internetDomain) ); 
      }
      $user = null;
      try {
         $this->ldap->bind($this->domain . '\\' .$username, $credentials['password']);
         $bindSuccessfull = true;
      } catch (ConnectionException $e) {
         $bindSuccessfull = false;
      }
      if ($bindSuccessfull) {
         $user = $this->updateUserFromLdap($credentials);
      } else {
         $user = $this->userManager->findUserByUsername($username);
      }
      if (null === $user) {
         throw new CustomUserMessageAuthenticationException('user_not_found');
      }
      if (!$user->getActivated()) {
         throw new CustomUserMessageAuthenticationException('user_deactivated');
      }
      $passport = new Passport(
         new UserBadge($username, function ($userIdentifier) {
            return $this->userManager->findUserByUsername(['username' => $userIdentifier]);
      }), new PasswordCredentials($credentials['password']),[
         new CsrfTokenBadge('authenticate', $credentials['csrf_token']),
         new RememberMeBadge(),
      ]);
      $passport->addBadge(new PasswordUpgradeBadge($credentials['password'], $this->userRepository));
      $user = $this->userManager->updateLastLogin($user);

      return $passport;
   }

   private function addUser($newUser, $password)
   {
      $user = $this->userManager->newUser(
         $newUser->getAttribute($this->ldapUsersUuid)[0],
         $password,
         $newUser->getAttribute('givenName')[0],
         $newUser->getAttribute('mail')[0],
         [],
         true,
         new \DateTime(),
         $newUser->getAttribute('uid') !== null ? $newUser->getAttribute('uid')[0]: null,
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
      if (get_class($exception) === 'Symfony\\Component\\Security\\Core\\Exception\\BadCredentialsException') {
         $exception = new CustomUserMessageAuthenticationException('user_not_found');
      }
      if ($request->hasSession()) {
         $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
      }

      return new RedirectResponse($this->getLoginUrl($request));
   }

   public function getPassword($credentials): ?string
   {
      return ($credentials['password']);
   }

   /**  
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

   /**
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
      if ( strpos($credentials['username'],$this->internetDomain) > 0 ) {
         $username = substr($credentials['username'], 0, strpos($credentials['username'],$this->internetDomain) ); 
      } else {
         $username = $credentials['username'];
      }
      $filledFilter = str_replace('{username}', $username, $this->ldapUsersFilter);
      $query = $this->ldap->query($this->ldapUserDn, $filledFilter);
      $results = $query->execute()->toArray();
      $dbUser = $this->userManager->findUserByUsername($username);
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
