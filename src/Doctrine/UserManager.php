<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AMREU\UserBundle\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;

// use MiPago\Bundle\Model\UserManager as BaseUserManager;
/**
 * Description of PaymentManager.
 *
 * @author ibilbao
 */
class PaymentManager
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var string
     */
    private $class;

    public function __construct(ObjectManager $om, $class)
    {
        $this->objectManager = $om;
        $this->class = $class;
    }

    protected function getClass()
    {
        if (false !== strpos($this->class, ':')) {
            $metadata = $this->objectManager->getClassMetadata($this->class);
            $this->class = $metadata->getName();
        }

        return $this->class;
    }

    /**
     * @return ObjectRepository
     */
    public function getRepository()
    {
        return $this->objectManager->getRepository($this->getClass());
    }

    public function newUser()
    {
        $class = $this->getClass();
        $user = new $class();

        return $user;
    }

    public function saveUser(Symfony\Component\Security\Core\User\UserInterface $user)
    {
        $this->objectManager->persist($user);
        $this->objectManager->flush();
    }
}
