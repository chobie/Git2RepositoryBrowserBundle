<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();
$collection->add('ChobieGit2RepositoryBrowserBundle_homepage', new Route('/hello/{name}', array(
    '_controller' => 'ChobieGit2RepositoryBrowserBundle:Default:index',
)));

return $collection;
