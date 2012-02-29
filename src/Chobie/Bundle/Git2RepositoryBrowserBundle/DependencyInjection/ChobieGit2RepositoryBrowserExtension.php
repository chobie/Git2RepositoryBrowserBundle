<?php

namespace Chobie\Bundle\Git2RepositoryBrowserBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ChobieGit2RepositoryBrowserExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->bindParameters($container, 'chobie_git2_repository_browser', $config);

        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');
        $loader->load('config.php');

    }

    public function getAlias()
    {
        return 'chobie_git2_repository_browser';
    }

    public function bindParameters(ContainerBuilder $container, $name, $config)
    {
        if(is_array($config))
        {
            foreach ($config as $key => $value)
            {
                $this->bindParameters($container, $name.'.'.$key, $value);
            }
        }
        else
        {
            $container->setParameter($name, $config);
        }
    }}
