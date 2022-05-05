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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager implements UserManagerInterface
{
    protected $om;
    private $class;
    private $passwordEncoder;

    public function __construct(ObjectManager $om = null, $class = null, UserPasswordHasherInterface $passwordEncoder)
    {
        $this->om = $om;
        $this->class = $class;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function newUser(string $username, string $password, string $firstName, string $email, array $roles, $activated = true, $lastLogin = null, $idNumber = null): UserInterface
    {
        $class = $this->class;
        $user = new $class();
        /** @var UserInterface $user */
        $user->setUserName($username);
        $user->setRoles($roles);
        $user->setFirstName($firstName);
        $user->setEmail($email);
        $encodedPassword = $this->passwordEncoder->hashPassword($user, $password);
        $user->setPassword($encodedPassword);
        $user->setActivated($activated);
        $user->setLastLogin($lastLogin);
        $user->setIdNumber($idNumber);
        $this->om->persist($user);
        $this->om->flush();

        return $user;
    }

    /**
     * Updates the user.
     *
     * @param UserInterface $user the user to be updated
     *
     * @return UserInterface
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
     * @return UserInterface
     *
     * @throws Exception
     */
    public function promoteUser($username, $roles): UserInterface
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
     * @return UserInterface
     *
     * @throws Exception
     */
    public function demoteUser($username, $roles): UserInterface
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
     * @return UserInterface
     *
     * @throws Exception
     */
    private function setActivatedTo($username, $status): UserInterface
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
     * @return UserInterface
     *
     * @throws Exception
     */
    public function activateUser($username): UserInterface
    {
        return $this->setActivatedTo($username, true);
    }

    /**
     * Deactivate the specified user.
     *
     * @param string $username
     *
     * @return UserInterface
     *
     * @throws Exception
     */
    public function deactivateUser($username): UserInterface
    {
        return $this->setActivatedTo($username, false);
    }

    /**
     * Find a user by username or returns
     * Returns null if not found.
     *
     * @param string $username
     *
     * @return UserInterface|null
     */
    public function findUserByUsername($username): ?UserInterface
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
     * @return UserInterface|null
     */
    public function findUserByEmail($email): ?UserInterface
    {
        $user = $this->om->getRepository($this->class)->findOneBy(['email' => $email]);

        return $user;
    }

    /**
     * Find all users.
     *
     * @return array|null
     */
    public function findAll()
    {
        $user = $this->om->getRepository($this->class)->findAll();

        return $user;
    }

    /**
     * Find user by id.
     *
     * @return UserInterface|null
     */
    public function find($id): ?UserInterface
    {
        $user = $this->om->getRepository($this->class)->find($id);

        return $user;
    }

    /**
     * Updates the user's password.
     * User can not be null.
     *
     * @param UserInterface $user
     *
     * @return UserInterface
     */
    public function updatePassword($user, $password): UserInterface
    {
        $user->setPassword($this->passwordEncoder->hashPassword($user, $password));
        $this->om->persist($user);
        $this->om->flush();

        return $user;
    }

    /**
     * Updates the user's last login date.
     * User can not be null.
     *
     * @param UserInterface $user
     *
     * @return UserInterface
     */
    public function updateLastLogin($user): UserInterface
    {
        $user->setLastLogin(new DateTime());
        $this->om->persist($user);
        $this->om->flush();

        return $user;
    }

    /**
     * Returns the user's repository
     *
     * @return PasswordUpgraderInterface
     */
    public function getRepository()
    {
        return $this->om->getRepository($this->class);
    }
}
