<?php

namespace ContainerQO5u4tG;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * @internal This class has been auto-generated by the Symfony Dependency Injection Component.
 */
class getFakeServiceService extends testsAMREU_UserBundle_Test_UserBundleTestingKernelTestDebugContainer
{
    /**
     * Gets the public 'AMREU\UserBundle\Service\FakeService' shared service.
     *
     * @return \AMREU\UserBundle\Service\FakeService
     */
    public static function do($container, $lazyLoad = true)
    {
        include_once \dirname(__DIR__, 4).'\\src\\Service\\FakeService.php';

        return $container->services['AMREU\\UserBundle\\Service\\FakeService'] = new \AMREU\UserBundle\Service\FakeService();
    }
}
