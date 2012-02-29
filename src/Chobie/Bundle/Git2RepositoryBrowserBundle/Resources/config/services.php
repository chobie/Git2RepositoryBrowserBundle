<?php

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;


$def = new Definition('Chobie\Bundle\Git2RepositoryBrowserBundle\Twig\Extension\ChobieGit2RepositoryBrowserBundleExtension');
$def->addTag('twig.extension');

$container->setDefinition(
    'chobie_git2_repository_browser.twig.extension', $def

);