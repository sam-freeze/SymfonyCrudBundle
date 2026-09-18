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
        // TreeBuilder's no-arg constructor (relying on a separate ->root()
        // call) was removed in symfony/config 5.0 - the root name is now
        // required directly in the constructor.
        return new TreeBuilder('symfony_crud');
    }
}
