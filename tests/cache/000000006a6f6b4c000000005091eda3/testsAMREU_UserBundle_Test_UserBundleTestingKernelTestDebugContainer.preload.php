<?php

// This file has been auto-generated by the Symfony Dependency Injection Component
// You can reference it in the "opcache.preload" php.ini setting on PHP >= 7.4 when preloading is desired

use Symfony\Component\DependencyInjection\Dumper\Preloader;

require dirname(__DIR__, 3).'\\vendor/autoload.php';
require __DIR__.'/ContainerQO5u4tG/testsAMREU_UserBundle_Test_UserBundleTestingKernelTestDebugContainer.php';
require __DIR__.'/ContainerQO5u4tG/getFakeServiceService.php';

$classes = [];
$classes[] = 'AMREU\UserBundle\UserBundle';
$classes[] = 'AMREU\UserBundle\Service\FakeService';
$classes[] = 'Symfony\Component\DependencyInjection\ContainerInterface';

Preloader::preload($classes);
