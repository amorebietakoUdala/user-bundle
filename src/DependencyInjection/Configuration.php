<?php

namespace AMREU\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Description of Configuration.
 *
 * @author ibilbao
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): \Symfony\Component\Config\Definition\Builder\TreeBuilder
    {
        $treebuilder = new \Symfony\Component\Config\Definition\Builder\TreeBuilder('user');
        $rootNode = $treebuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('object_manager')->defaultValue('doctrine.orm.default_entity_manager')->info('Doctrine class')->end()
                ->scalarNode('class')->defaultValue('App\Entity\User')->info('User Class')->end()
                ->arrayNode('allowedRoles')
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('form_type')->defaultValue('AMREU\UserBundle\Form\UserType')->info('User registration form type')->end()
                ->scalarNode('domain')->info('Domain')->end()
                ->scalarNode('ldap_users_dn')->info('LDAP\'s user dn')->end()
                ->scalarNode('ldap_users_filter')->info('LDAP\'s user\'s filter')->end()
                ->scalarNode('ldap_users_uuid')->defaultValue('sAMAccountName')->info('LDAP\'s user\'s uuid. For example: sAMAccountName')->end()
                ->scalarNode('successPath')->defaultValue('/')->info('Path to go on successfull login')->end()
                ->scalarNode('ldap_user')->info('The user that will search in the LDAP')->end()
                ->scalarNode('ldap_password')->info('The password of the user that will search in the LDAP')->end()
            ->end();

        return $treebuilder;
    }
}
