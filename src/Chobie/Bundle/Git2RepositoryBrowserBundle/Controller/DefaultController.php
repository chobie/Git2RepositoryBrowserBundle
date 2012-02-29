<?php

namespace Chobie\Bundle\Git2RepositoryBrowserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Finder\Finder;
use Chobie\Bundle\Git2RepositoryBrowserBundle\Util\Albino;
use Chobie\Bundle\Git2RepositoryBrowserBundle\Util\VersionSorter;
use Chobie\Bundle\Git2RepositoryBrowserBundle\Util\Blame;
use Chobie\Bundle\Git2RepositoryBrowserBundle\Util\Diff;
use Git2;

class DefaultController extends Controller
{
    /**
     * @param $name
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $dir = $this->container->getParameter("chobie_git2_repository_browser.repository_path");
        $itr = Finder::create()->directories()->depth(0)->name('*')->in($dir);
        $entries = array();
        foreach($itr as $entry) {
            /* @var \Symfony\Component\Finder\SplFileInfo $entry */
            $entries[] = $entry->getFilename();
        }

        return $this->render('ChobieGit2RepositoryBrowserBundle:Default:index.html.twig', array(
            'repositories' => $entries,
        ));
    }

    public function iconAction($name)
    {
        $data = file_get_contents(__DIR__ . "/../Resources/public/images/icons/$name");

        header("Content-type: image/png");
        echo $data;
        exit;
    }

    public function blobAction($repository_name, $refs, $name)
    {
        $path = $this->getRepositoryPath($repository_name);
        if ($refs != "HEAD") {
            $refs = "refs/heads/{$refs}";
        }

        $repo = new \Git2\Repository($path);
        $ref = \Git2\Reference::lookup($repo, $refs);
        $ref = $ref->resolve();
        $commit = $repo->lookup($ref->getTarget());

        $tree = $commit->getTree();
        $basename = basename($name);
        $dirname = ltrim(dirname($name) . "/" ,"./");


        if ($dirname != "") {
            $tree = $tree->getSubTree($dirname);
        }

        foreach ($tree as $entry) {
            if($entry->name == $basename) {
                break;
            }
        }

        $blob = $repo->lookup($entry->oid);

        $img = false;
        $data = '';
        $ext = pathinfo($name,\PATHINFO_EXTENSION);
        switch ($ext) {
            case 'jpg':
            case 'gif':
            case 'png':
                $img = true;
                break;
            case 'mardkwon':
            case 'md':
                $sd = new \Sundown($blob->getContent());
                $data = $sd->to_html();
                break;
            default:
                if ($ext == "htm"){
                    $ext = "html+jinja";
                } else if ($ext == "twig") {
                    $ext = "html+jinja";
                }

                $data = Albino::colorize($blob->getContent(),$ext);
                if ($data){
                    $data = preg_replace("|</div>|","",preg_replace("|<div class=\"highlight\">|","",preg_replace("|</?pre>|m","",$data)));
                    $lines = explode("\n",$data);
                    foreach ($lines as $o => $line) {
                        $lines[$o] = "<div class=\"line\">" . $line . "</div>";
                    }
                    $data = "<div class=\"highlight\"><pre>" . join("",$lines) . "</pre></div>";
                }
                break;
        }
        if (!$data) {
            $data = htmlspecialchars($blob->getContent());
        }

        $meta = array();
        foreach ($tree as $entry) {
            $commit_id = trim(`GIT_DIR={$path} git log --format=%H -n1 -- {$dirname}{$entry->name}`);
            $meta[$entry->name] = $repo->lookup($commit_id);
        }


        return $this->render('ChobieGit2RepositoryBrowserBundle:Default:blob.html.twig', array(
            'name' => $name,
            'tree' => $tree,
            'dirname' => $dirname,
            'blob' => $blob,
            'repository_name' => $repository_name,
            'img' => $img,
            'data' => $data,
            'commit' => $commit,
            'meta' => $meta,
            'branch_count' => $this->getBranchCount($repo),
            'tags_count' => $this->getTagsCount($repo),
            'refs' => basename($refs),
        ));
    }

    public function treeindexAction($repository_name, $refs)
    {
        $path  = $this->getRepositoryPath($repository_name);

        if ($refs != "HEAD") {
            $refs = "refs/heads/{$refs}";
        }

        $repo = new \Git2\Repository($path);
        $ref = \Git2\Reference::lookup($repo, $refs);
        $ref = $ref->resolve();
        $refs = $ref->getBaseName();

        $commit = $repo->lookup($ref->getTarget());
        $tree = $commit->getTree();
        $entry = $tree->getEntryByName("README.md");

        $data = '';
        if ($entry) {
            $blob = $repo->lookup($entry->oid);
            $sd = new \Sundown($blob->getContent());
            $data = $sd->toHtml();
        }
        $meta = array();
        foreach ($tree as $entry) {
            $commit_id = trim(`GIT_DIR={$path} git log --format=%H -n1 -- {$entry->name}`);
            $meta[$entry->name] = $repo->lookup($commit_id);
        }


        return $this->render('ChobieGit2RepositoryBrowserBundle:Default:tree.html.twig', array(
            'tree' => $commit->getTree(),
            'dirname' => '',
            'repository_name' => $repository_name,
            'data' => $data,
            'commit' => $commit,
            'meta' => $meta,
            'branch_count' => $this->getBranchCount($repo),
            'tags_count' => $this->getTagsCount($repo),
            'refs' => $refs,
        ));
    }

    protected function getBranchCount(\Git2\Repository $repo)
    {
        return count(\Git2\Reference::each($repo, 1, function($name){
            return preg_match("!refs/heads/!",$name);
        }));
    }

    protected function getTagsCount(\Git2\Repository $repo)
    {
        return count(\Git2\Reference::each($repo, 1, function($name){
            return preg_match("!refs/tags/!",$name);
        }));
    }

    public function treeAction($repository_name, $refs, $name)
    {
        $path = $this->getRepositoryPath($repository_name);

        $absolute_refs = "refs/heads/" . $refs;

        $repo = new \Git2\Repository($path);
        $ref = \Git2\Reference::lookup($repo, $absolute_refs);
        $ref = $ref->resolve();

        $commit = $repo->lookup($ref->getTarget());
        $tree = $commit->getTree();

        $tree = $tree->getSubTree($name);

        $dirname = $name . "/";

        $meta = array();
        foreach ($tree as $entry) {
            $commit_id = trim(`GIT_DIR={$path} git log --format=%H -n1 -- {$dirname}{$entry->name}`);
            $meta[$entry->name] = $repo->lookup($commit_id);
        }

        $entry = $tree->getEntryByName("README.md");

        $data = '';
        if ($entry) {
            $blob = $repo->lookup($entry->oid);
            $sd = new \Sundown($blob->getContent());
            $data = $sd->toHtml();
        }

        return $this->render('ChobieGit2RepositoryBrowserBundle:Default:tree.html.twig', array(
            'name' => $name,
            'tree' => $tree,
            'dirname' => $dirname,
            'repository_name' => $repository_name,
            'data' => $data,
            'commit' => $commit,
            'meta' => $meta,
            'branch_count' => $this->getBranchCount($repo),
            'tags_count' => $this->getTagsCount($repo),
            'refs' => $refs,
        ));

    }


    protected function getRepositoryPath($name)
    {
        return sprintf("%s/%s/",
            $this->container->getParameter("chobie_git2_repository_browser.repository_path"),
            $name
        );
    }

    protected function getRepository($name)
    {

        return new \Git2\Repository($this->getRepositoryPath($name));
    }

    public function commitsAction($repository_name, $refs)
    {
        $repo = $this->getRepository($repository_name);

        if ($refs != "HEAD") {
            $refs = "refs/heads/{$refs}";
        }

        try {
            $ref = \Git2\Reference::lookup($repo, $refs);
            $ref = $ref->resolve();
            $commit = $repo->lookup($ref->getTarget());

            $walker = new Git2\Walker($repo);
            $walker->push($commit->getOId());

            $i=0;
            $commits = array();
            foreach($walker as $tmp) {
                if ($i>20) break;

                $commits[] = $tmp;
                $i++;
            }
        } catch (\InvalidArgumentException $e) {
            $commits = array();
        }

        return $this->render('ChobieGit2RepositoryBrowserBundle:Default:commits.html.twig', array(
            'repository_name' => $repository_name,
            'commits' => $commits,
            'commit' => null,
            'branch_count' => $this->getBranchCount($repo),
            'tags_count' => $this->getTagsCount($repo),
            'refs' => basename($refs),
        ));
    }

    public function commitAction($repository_name, $commit_id)
    {
        $repo = $this->getRepository($repository_name);

        $n_commit  = escapeshellarg($commit_id);
        $repository_path = $this->getRepositoryPath($repository_name);
        $stat = `GIT_DIR={$repository_path} git log -p {$n_commit} -n1`;
        $struct = Diff\Parser::parse($stat);

        $commit = $repo->lookup($commit_id);
        return $this->render('ChobieGit2RepositoryBrowserBundle:Default:commit.html.twig', array(
            'repository_name' => $repository_name,
            'commit' => $commit,
            'diff' => $struct,
            'branch_count' => $this->getBranchCount($repo),
            'tags_count' => $this->getTagsCount($repo),
            'refs'  => "HEAD",
        ));
    }

    public function branchesAction($repository_name)
    {
        $repo = $this->getRepository($repository_name);
        return $this->render('ChobieGit2RepositoryBrowserBundle:Default:branches.html.twig', array(
            'repository_name' => $repository_name,
            'commit' => null,
            'branches' => \Git2\Reference::each($repo,0,function($name){return preg_match("!refs/heads!",$name);}),
            'branch_count' => $this->getBranchCount($repo),
            'tags_count' => $this->getTagsCount($repo),
            'refs' => "HEAD",
        ));
    }

    public function tagsAction($repository_name)
    {
        $repo = $this->getRepository($repository_name);
        $refs = array();
        $for_sort = array();
        $tags = array();
        $tmp = array();
        foreach (\Git2\Reference::each($repo,0,function($name){return preg_match("!refs/tags!",$name);}) as $ref) {
            $tmp[$ref->getBaseName()] = $ref;
            $for_sort[] = $ref->getBaseName();
        }

        foreach(VersionSorter::rsort($for_sort) as $name){
            $tags[] = $tmp[$name];
        }

        return $this->render('ChobieGit2RepositoryBrowserBundle:Default:tags.html.twig', array(
            'repository_name' => $repository_name,
            'commit' => null,
            'tags' => $tags,
            'branch_count' => $this->getBranchCount($repo),
            'tags_count' => $this->getTagsCount($repo),
            'refs' => "HEAD",
        ));
    }

    public function blameAction($repository_name, $name)
    {
        $repo = $this->getRepository($repository_name);

        $path = $this->getRepositoryPath($repository_name);
        $stat = `GIT_DIR={$path} git blame -p master -- {$name}`;
        $blame =  Blame\Parser::parse($stat);

        return $this->render('ChobieGit2RepositoryBrowserBundle:Default:blame.html.twig', array(
            'repository_name' => $repository_name,
            'commit' => null,
            'blame' => $blame,
            'branch_count' => $this->getBranchCount($repo),
            'tags_count' => $this->getTagsCount($repo),
            'refs' => "HEAD",
        ));
    }

}
