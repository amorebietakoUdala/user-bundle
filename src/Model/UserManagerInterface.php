<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AMREU\UserBundle\Model;

/**
 * Description of UserManagerInterface.
 *
 * @author ibilbao
 */
interface UserManagerInterface
{
    /**
     * Creates an empty user instance. Without storing it.
     *
     * @return User
     */
    public function newEmptyUser();

    /**
     * Creates and stores a new User with the given params.
     *
     * @return User
     *
     * @param type $username
     * @param type $password
     * @param type $firstName
     * @param type $email
     * @param type $roles
     */
    public function newUser($username, $password, $firstName, $email, $roles, $activated = true, $lastLogin = null);

    /**
     * Assigns the specified roles to a user.
     *
     * @param string $username
     * @param array  $roles
     *
     * @return User
     */
    public function promoteUser($username, $roles);

    /**
     * Removes the specified roles from a user.
     *
     * @param string $username
     * @param array  $roles
     *
     * @return User
     */
    public function demoteUser($username, $roles);

    /**
     * Deletes the specified user.
     *
     * @param string $username
     *
     * @return User
     */
    public function deleteUser($username);

    /**
     * Find a user by username or returns
     * Returns null if not found.
     *
     * @param string $username
     *
     * @return User|null
     */
    public function findUserByUsername($username);

    /**
     * Find a user by email or returns
     * Returns null if not found.
     *
     * @param string $username
     *
     * @return User|null
     */
    public function findUserByEmail($email);

    /**
     * Updates a user.
     *
     * @param UserInterface $user
     */
    public function updateUser(UserInterface $user);

    /**
     * Updates password for the specified user.
     *
     * @param User $user
     * @param string password
     *
     * @return User
     */
    public function updatePassword($user, $password);

    /**
     * Updates last login date for the specified user.
     *
     * @param User $user
     *
     * @return User
     */
    public function updateLastLogin($user);
}
