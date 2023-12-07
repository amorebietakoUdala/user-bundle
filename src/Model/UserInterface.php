<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AMREU\UserBundle\Model;

use DateTime;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface as PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface as CoreUserInterface;

/**
 * @author ibilbao
 */
interface UserInterface extends CoreUserInterface, PasswordAuthenticatedUserInterface
{
    public function getId(): ?int;

    public function getUsername(): string;

    public function setUsername(string $username);

    public function getRoles(): array;

    public function setRoles(array $roles);

    public function getPassword(): ?string;

    public function setPassword(string $password);

    public function getSalt(): ?string;

    public function eraseCredentials() : void;

    public function getFirstName(): ?string;

    public function setFirstName(string $firstName);

    public function getEmail(): ?string;

    public function setEmail(string $email);

    public function getActivated(): bool;

    public function setActivated(bool $activated);

    public function getLastLogin(): DateTime;

    public function setLastLogin(DateTime $lastLogin = null);

    public function getIdNumber(): ?string;

    public function setIdNumber(string $idNumber);

    public function getUserIdentifier(): string;

    public function __toString();
}
