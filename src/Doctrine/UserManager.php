<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AMREU\UserBundle\Doctrine;

use AMREU\UserBundle\Model\UserInterface;
use AMREU\UserBundle\Model\UserManagerInterface;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

//use Symfony\Component\Security\Core\User\UserInterface;

class UserManager implements UserManagerInterface
{
    protected $om;
    private $class;
    private $passwordEncoder;

    public function __construct(ObjectManager $om = null, $class = null, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->om = $om;
        $this->class = $class;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function newEmptyUser()
    {
        $class = $this->class;
        $user = new $class();
        $user->setRoles(['ROLE_USER']);

        return $user;
    }

    public function newUser($username, $password, $firstName, $email, $roles, $activated = true, $lastLogin = null): UserInterface
    {
        $user = $this->newEmptyUser();
        /* @var $user AMREUUserInterface */
        $user->setUserName($username);
        $user->setRoles($roles);
        $user->setFirstName($firstName);
        $user->setEmail($email);
        $encodedPassword = $this->passwordEncoder->encodePassword($user, $password);
        $user->setPassword($encodedPassword);
        $user->setActivated($activated);
        $user->setLastLogin($lastLogin);
        $this->om->persist($user);
        $this->om->flush();

        return $user;
    }

    /**
     * Updates the user.
     *
     * @param UserInterface2 $user the user to be updated
     *
     * @return UserInterface2
     *
     * @throws Exception
     */
    public function updateUser(UserInterface $user)
    {
        $this->om->persist($user);
        $this->om->flush();

        return $user;
    }

    /**
     * Assings the specified roles to user.
     *
     * @param string $username
     * @param array  $roles
     *
     * @return UserInterface2
     *
     * @throws Exception
     */
    public function promoteUser($username, $roles)
    {
        $user = $this->findUserByUsername($username);
        if (null === $user) {
            throw new Exception('Username not found. Can\'t promote.');
        }
        $actualRoles = $user->getRoles();
        $user->setRoles(array_unique(array_merge($actualRoles, $roles)));
        $this->om->persist($user);
        $this->om->flush();

        return $user;
    }

    /**
     * Removes the specified roles from the user.
     *
     * @param type $username
     * @param type $roles
     *
     * @return UserInterface2
     *
     * @throws Exception
     */
    public function demoteUser($username, $roles)
    {
        $user = $this->findUserByUsername($username);
        if (null === $user) {
            throw new Exception('Username not found. Can\'t demote.');
        }
        $actualRoles = $user->getRoles();
        $user->setRoles(array_unique(array_diff($actualRoles, $roles)));
        $this->om->persist($user);
        $this->om->flush();

        return $user;
    }

    /**
     * Deletes the specified user.
     *
     * @param string $username
     *
     * @throws Exception
     */
    public function deleteUser($username)
    {
        $user = $this->findUserByUsername($username);
        if (null === $user) {
            throw new Exception('Username not found. Can\'t delete.');
        }
        $this->om->remove($user);
        $this->om->flush();
    }

    /**
     * Set activated to true/false.
     *
     * @param string $username
     *
     * @return UserInterface2
     *
     * @throws Exception
     */
    private function setActivatedTo($username, $status)
    {
        $user = $this->findUserByUsername($username);
        if (null === $user) {
            throw new Exception('Username not found. Can\'t delete.');
        }
        $user->setActivated($status);
        $this->om->persist($user);
        $this->om->flush();

        return $user;
    }

    /**
     * Activate the specified user.
     *
     * @param string $username
     *
     * @return UserInterface2
     *
     * @throws Exception
     */
    public function activateUser($username)
    {
        return $this->setActivatedTo($username, true);
    }

    /**
     * Deactivate the specified user.
     *
     * @param string $username
     *
     * @return UserInterface2
     *
     * @throws Exception
     */
    public function deactivateUser($username)
    {
        return $this->setActivatedTo($username, false);
    }

    /**
     * Find a user by username or returns
     * Returns null if not found.
     *
     * @param string $username
     *
     * @return UserInterface2|null
     */
    public function findUserByUsername($username)
    {
        $user = $this->om->getRepository($this->class)->findOneBy(['username' => $username]);

        return $user;
    }

    /**
     * Find a user by email or returns
     * Returns null if not found.
     *
     * @param string $username
     *
     * @return UserInterface2|null
     */
    public function findUserByEmail($email)
    {
        $user = $this->om->getRepository($this->class)->findOneBy(['email' => $email]);

        return $user;
    }

//    /**
//     * Find a user by username or returns
//     * Returns null if not found.
//     *
//     * @param string $username
//     *
//     * @return AMREUUserInterface|null
//     */
//    public function loadUserByUsername(string $username)
//    {
//        return $this->findUserByUsername($username);
//    }

    /**
     * Updates the user's password.
     * User can not be null.
     *
     * @param AMREUUserInterface $user
     *
     * @return AMREUUserInterface
     */
    public function updatePassword($user, $password)
    {
        $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
        $this->om->persist($user);
        $this->om->flush();

        return $user;
    }

    /**
     * Updates the user's last login date.
     * User can not be null.
     *
     * @param AMREUUserInterface $user
     *
     * @return AMREUUserInterface
     */
    public function updateLastLogin($user)
    {
        $user->setLastLogin(new DateTime());
        $this->om->persist($user);
        $this->om->flush();

        return $user;
    }
}
