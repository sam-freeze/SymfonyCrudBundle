<?php

namespace SamFreeze\SymfonyCrudBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration options of the bundle
 *
 * @author Samuel BUCHER <samuel.bucher@outlook.fr>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('symfony_crud');
        return $treeBuilder;
    }
}
