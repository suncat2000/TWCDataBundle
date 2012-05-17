<?php

namespace SunCat\TWCDataBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('twc_data');

        $rootNode
            ->children()
                ->scalarNode('apikey')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('format')->defaultValue('json')
                    ->validate()
                        ->ifNotInArray(array('json', 'xml'))
                        ->thenInvalid('Invalid {format} for doctype param "%s"')
                    ->end()
                ->end()
                ->scalarNode('units')->defaultValue('m')
                    ->validate()
                        ->ifNotInArray(array('m', 's'))
                        ->thenInvalid('Invalid {units} for units param "%s"')
                    ->end()
                ->end()
                ->scalarNode('host')->defaultValue('http://api.theweatherchannel.com')->cannotBeEmpty()->end()
                ->scalarNode('locale')->defaultValue('en_GB')->cannotBeEmpty()->end()
                ->scalarNode('country')->defaultValue('UK')->cannotBeEmpty()->end()
            ->end();

        return $treeBuilder;
    }
}
