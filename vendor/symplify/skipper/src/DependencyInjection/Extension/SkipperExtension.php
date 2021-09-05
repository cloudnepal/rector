<?php

declare (strict_types=1);
namespace RectorPrefix20210905\Symplify\Skipper\DependencyInjection\Extension;

use RectorPrefix20210905\Symfony\Component\Config\FileLocator;
use RectorPrefix20210905\Symfony\Component\DependencyInjection\ContainerBuilder;
use RectorPrefix20210905\Symfony\Component\DependencyInjection\Extension\Extension;
use RectorPrefix20210905\Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
final class SkipperExtension extends \RectorPrefix20210905\Symfony\Component\DependencyInjection\Extension\Extension
{
    /**
     * @param string[] $configs
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder
     */
    public function load($configs, $containerBuilder) : void
    {
        // needed for parameter shifting of sniff/fixer params
        $phpFileLoader = new \RectorPrefix20210905\Symfony\Component\DependencyInjection\Loader\PhpFileLoader($containerBuilder, new \RectorPrefix20210905\Symfony\Component\Config\FileLocator(__DIR__ . '/../../../config'));
        $phpFileLoader->load('config.php');
    }
}
