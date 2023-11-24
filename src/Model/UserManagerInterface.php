<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AMREU\UserBundle\Model;

use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use AMREU\UserBundle\Model\UserInterface;

/**
 * Description of UserManagerInterface.
 *
 * @author ibilbao
 */
interface UserManagerInterface
{
    /**
     * Creates and stores a new User with the given params.
     *
     * @return UserInterface
     *
     * @param string $username
     * @param string $password
     * @param string $firstName
     * @param string $email
     * @param array $roles
     */
    public function newUser(string $username, string $password, string $firstName, string $email, array $roles, $activated = true, $lastLogin = null, $idNumber = null): UserInterface;

    /**
     * Assigns the specified roles to a user.
     *
     * @param string $username
     * @param array  $roles
     *
     * @throws Exception
     * 
     * @return UserInterface
     */
    public function promoteUser($username, $roles): UserInterface;

    /**
     * Removes the specified roles from a user.
     *
     * @param string $username
     * @param array  $roles
     *
     * @throws Exception
     * 
     * @return UserInterface
     */
    public function demoteUser($username, $roles): UserInterface;

    /**
     * Deletes the specified user.
     *
     * @param string $username
     *
     */
    public function deleteUser($username);

    /**
     * Find a user by username or returns
     * Returns null if not found.
     *
     * @param string $username
     *
     * @return UserInterface|null
     */
    public function findUserByUsername($username): ?UserInterface;

    /**
     * Find a user by email or returns
     * Returns null if not found.
     *
     * @param string $username
     *
     * @return UserInterface|null
     */
    public function findUserByEmail($email): ?UserInterface;

    /**
     * Find all users.
     *
     * @return array|null
     */
    public function findAll(): ?array;

    /**
     * Find user by id.
     *
     * @return UserInterface|null
     */
    public function find(string $id): UserInterface|null;

    /**
     * Updates a user.
     *
     * @param UserInterface $user
     */
    public function updateUser(UserInterface $user);

    /**
     * Updates password for the specified user.
     *
     * @param UserInterface $user
     * @param string password
     *
     * @return UserInterface
     */
    public function updatePassword($user, $password): UserInterface;

    /**
     * Updates last login date for the specified user.
     *
     * @param UserInterface $user
     *
     * @return UserInterface
     */
    public function updateLastLogin($user): UserInterface;

    /**
     * Returns the user's repository
     *
     * @return PasswordUpgraderInterface
     */
    public function getRepository(): PasswordUpgraderInterface;
}
