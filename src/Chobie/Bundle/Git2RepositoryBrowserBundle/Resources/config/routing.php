<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();
$collection->add('ChobieGit2RepositoryBrowserBundle_homepage', new Route('/', array(
    '_controller' => 'ChobieGit2RepositoryBrowserBundle:Default:index',
)));

/* Tree */
$collection->add('ChobieGit2RepositoryBrowserBundle_tree_index', new Route('/{repository_name}/tree/{refs}', array(
    '_controller' => 'ChobieGit2RepositoryBrowserBundle:Default:treeindex',
)));

$route = new Route('/{repository_name}/tree/{refs}/{name}', array(
    '_controller' => 'ChobieGit2RepositoryBrowserBundle:Default:tree'
));
$route->setRequirement("name",".+");
$collection->add('ChobieGit2RepositoryBrowserBundle_tree',$route);

/* Blob */
$route = new Route('/{repository_name}/blob/{refs}/{name}', array(
    '_controller' => 'ChobieGit2RepositoryBrowserBundle:Default:blob'
));
$route->setRequirement("name",".+");
$collection->add('ChobieGit2RepositoryBrowserBundle_blob',$route);

/* Commit */
$route = new Route('/{repository_name}/commits/{refs}', array(
    '_controller' => 'ChobieGit2RepositoryBrowserBundle:Default:commits'
));
$collection->add('ChobieGit2RepositoryBrowserBundle_commits',$route);

$route = new Route('/{repository_name}/commit/{commit_id}', array(
    '_controller' => 'ChobieGit2RepositoryBrowserBundle:Default:commit'
));
$collection->add('ChobieGit2RepositoryBrowserBundle_commit',$route);

/* Branches */
$route = new Route('/{repository_name}/branches', array(
    '_controller' => 'ChobieGit2RepositoryBrowserBundle:Default:branches'
));
$collection->add('ChobieGit2RepositoryBrowserBundle_branches',$route);

$route = new Route('/{repository_name}/tags', array(
    '_controller' => 'ChobieGit2RepositoryBrowserBundle:Default:tags'
));
$collection->add('ChobieGit2RepositoryBrowserBundle_tags',$route);

/* Blame */
$route = new Route('/{repository_name}/blame/{name}', array(
    '_controller' => 'ChobieGit2RepositoryBrowserBundle:Default:blame'
));
$route->setRequirement("name",".+");
$collection->add('ChobieGit2RepositoryBrowserBundle_blame',$route);


/* SmartProtocol */
$route = new Route('/{repository_name}/{task}', array(
    '_controller' => 'ChobieGit2RepositoryBrowserBundle:SmartHTTPTransport:default'
));
$route->setRequirement("repository_name",".+\\.git");
$route->setRequirement("task",".+");
$collection->add('ChobieGit2RepositoryBrowserBundle_transport',$route);

return $collection;