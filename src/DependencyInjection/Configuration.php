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
                ->scalarNode('class')->defaultValue('App\Entity\User')->info('User Class')->end();

        return $treebuilder;
    }
}
