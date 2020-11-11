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
     * Creates an empty user instance.
     *
     * @param string $username
     * @param string $password
     * @param string $firstName
     * @param string $email
     * @param array  $roles
     *
     * @return AMREU\UserBundle\Model\User
     */
    public function createUser($username, $password, $firstName, $email, $roles);

    /**
     * Assigns the specified roles to a user.
     *
     * @param string $username
     * @param array  $roles
     *
     * @return AMREU\UserBundle\Model\User
     */
    public function promoteUser($username, $roles);

    /**
     * Removes the specified roles from a user.
     *
     * @param string $username
     * @param array  $roles
     *
     * @return AMREU\UserBundle\Model\User
     */
    public function demoteUser($username, $roles);

    /**
     * Deletes the specified user.
     *
     * @param string $username
     *
     * @return AMREU\UserBundle\Model\User
     */
    public function deleteUser($username);

    /**
     * Find a user by username or returns
     * Returns null if not found.
     *
     * @param string $username
     *
     * @return AMREU\UserBundle\Model\User|null
     */
    public function findUserByUsername($username);

    /**
     * Updates password for the specified user.
     *
     * @param AMREU\UserBundle\Model\User $user
     * @param string password
     *
     * @return AMREU\UserBundle\Model\User
     */
    public function updatePassword($user, $password);

    /**
     * Updates last login date for the specified user.
     *
     * @param AMREU\UserBundle\Model\User $user
     *
     * @return AMREU\UserBundle\Model\User
     */
    public function updateLastLogin($user);
}
