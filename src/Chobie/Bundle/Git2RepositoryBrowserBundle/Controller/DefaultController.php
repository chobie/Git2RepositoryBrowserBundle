<?php

namespace Chobie\Bundle\Git2RepositoryBrowserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction($name)
    {
        return $this->render('ChobieGit2RepositoryBrowserBundle:Default:index.html.twig', array('name' => $name));
    }
}
