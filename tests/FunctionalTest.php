<?php

namespace AMREU\UserBundle\Test;

use AMREU\UserBundle\UserBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FunctionalTests.
 *
 * @author ibilbao
 */
class FunctionalTest extends TestCase
{
//    public function testServiceWiringWithConfiguration()
//    {
//        $kernel = new UserBundleTestingKernel([
//            'class' => '\App\Entity\User',
//        ]);
//        $kernel->boot();
//        $container = $kernel->getContainer();
//
//        $userController = $container->get('amreu.user_controller');
//        $this->assertIsString(\AMREU\UserBundle\Controller\UserController::class, $userController);
//    }
}

class UserBundleTestingKernel extends Kernel
{
    private $userBundleConfig;

    public function __construct(array $userBundleConfig = [])
    {
        $this->userBundleConfig = $userBundleConfig;
        parent::__construct('test', true);
    }

    public function registerBundles()
    {
        return [
            new UserBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->register('amreu.user_controller', \AMREU\UserBundle\Controller\UserController::class);
            $container->loadFromExtension('user', $this->userBundleConfig);
        });
    }

    public function getCacheDir()
    {
        return __DIR__.'/cache/'.spl_object_hash($this);
    }
}
