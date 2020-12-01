<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AMREU\UserBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Description of UserBundleExtension.
 *
 * @author ibilbao
 */
class UserExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        $definition = $container->getDefinition('amreu.user.manager');
        if (null !== $config['class']) {
            $definition->setArgument(1, $config['class']);
        }
        $definition = $container->getDefinition('amreu.login.form.authenticator');
        if (null !== $config['domain']) {
            $definition->setArgument(0, $config['domain']);
        }
        if (null !== $config['ldap_users_dn']) {
            $definition->setArgument(1, $config['ldap_users_dn']);
        }
        if (null !== $config['ldap_users_filter']) {
            $definition->setArgument(2, $config['ldap_users_filter']);
        }
        if (null !== $config['ldap_users_uuid']) {
            $definition->setArgument(3, $config['ldap_users_uuid']);
        }
        if (null !== $config['successPath']) {
            $definition->setArgument(4, $config['successPath']);
        }
        $definition = $container->getDefinition('amreu.user.controller');
        if (null !== $config['form_type']) {
            $definition->setArgument(0, $config['form_type']);
        }
        $definition = $container->getDefinition('amreu.user.form.factory');
        if (null !== $config['class']) {
            $definition->setArgument(0, $config['class']);
        }
        if (null !== $config['form_type']) {
            $definition->setArgument(1, $config['form_type']);
        }
        $definition = $container->getDefinition('amreu.user.form.type');
        if (null !== $config['class']) {
            $definition->setArgument(0, $config['class']);
        }
        if (null !== $config['allowedRoles']) {
            $definition->setArgument(1, $config['allowedRoles']);
        }
    }
}
