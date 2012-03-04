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
            $repo = $this->getRepository($entry->getFileName());
            $ref = $this->getDefaultReference($repo);

            $entries[] = array(
                "name" => $entry->getFilename(),
                "refs" => $ref->getBaseName(),
            );
        }

        return $this->render('ChobieGit2RepositoryBrowserBundle:Default:index.html.twig', array(
            'repositories' => $entries,
        ));
    }

    public function blobAction($repository_name, $refs, $name)
    {
        $path = $this->getRepositoryPath($repository_name);

        $repo = new \Git2\Repository($path);
        if ($refs != "HEAD") {
            $refs = $this->normalizeReference($repo,$refs);
        }

        $ref = \Git2\Reference::lookup($repo, $refs);
        $ref = $ref->resolve();
        $commit = $repo->lookup($ref->getTarget());
        if ($commit instanceof Git2\Tag) {
            $commit = $commit->getTarget();
        }

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
                $sd = new \Sundown\Markdown(new \Sundown\Render\HTML(array('autolink'=>true)), array('fenced_code_blocks'=>true));
                $data = $sd->render($blob->getContent());
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
            $commit_id = trim(`GIT_DIR={$path} git log {$ref->getName()} --format=%H -n1 -- {$dirname}{$entry->name}`);
            $meta[$entry->name] = $repo->lookup($commit_id);
        }

        $template = (isset($_REQUEST['_pjax'])) ?
            'ChobieGit2RepositoryBrowserBundle:Default:pjax.blob.html.twig':
            'ChobieGit2RepositoryBrowserBundle:Default:blob.html.twig';

        return $this->render($template, array(
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
            'active' => 'source',
            'is_image' => $img,
            'parts' => $this->getPartsWithName($repository_name, $name),
        ));
    }

    public function rawAction($repository_name, $refs, $name)
    {
        $path = $this->getRepositoryPath($repository_name);

        $repo = new \Git2\Repository($path);
        if ($refs != "HEAD") {
            $refs = $this->normalizeReference($repo,$refs);
        }

        $ref = \Git2\Reference::lookup($repo, $refs);
        $ref = $ref->resolve();
        $commit = $repo->lookup($ref->getTarget());
        if ($commit instanceof Git2\Tag) {
            $commit = $commit->getTarget();
        }

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
        $ext = pathinfo($entry->name,\PATHINFO_EXTENSION);

        switch ($ext) {
            case ".swf":
                $content_type = "application/x-shockwave-flash";
                break;
            case ".gif":
                $content_type = "image/jpeg";
                break;
            case ".jpeg":
            case ".jpg":
                $content_type = "image/jpeg";
                break;
            case ".png":
                $content_type = "image/png";
                break;
            default:
                $content_type = "text/plain";
        }

        return new \Symfony\Component\HttpFoundation\Response($blob->getContent(),200,array(
            "Content-type" => $content_type
        ));
    }

    public function treeindexAction($repository_name, $refs)
    {
        $path  = $this->getRepositoryPath($repository_name);


        $repo = new \Git2\Repository($path);
        if ($refs != "HEAD") {
            $refs = $this->normalizeReference($repo,$refs);
        }

        $ref = \Git2\Reference::lookup($repo, $refs);

        $ref = $ref->resolve();
        $refs = $ref->getBaseName();

        $commit = $repo->lookup($ref->getTarget());
        if ($commit instanceof Git2\Tag) {
            $commit = $commit->getTarget();
        }

        $tree = $commit->getTree();
        $entry = $tree->getEntryByName("README.md");

        $data = '';
        if ($entry) {
            $blob = $repo->lookup($entry->oid);
            $sd = new \Sundown\Markdown(new \Sundown\Render\HTML(), array(
                'fenced_code_blocks'=>true,
                'autolink'=>true
            ));
            $data = $sd->render($blob->getContent());
        }
        $meta = array();
        foreach ($tree as $entry) {
            $commit_id = trim(`GIT_DIR={$path} git log {$ref->getName()} --format=%H -n1 -- {$entry->name}`);
            $meta[$entry->name] = $repo->lookup($commit_id);
        }

        $template = (isset($_REQUEST['_pjax'])) ?
            'ChobieGit2RepositoryBrowserBundle:Default:pjax.tree.html.twig':
            'ChobieGit2RepositoryBrowserBundle:Default:tree.html.twig';


        return $this->render($template, array(
            'tree' => $commit->getTree(),
            'dirname' => '',
            'repository_name' => $repository_name,
            'data' => $data,
            'commit' => $commit,
            'meta' => $meta,
            'branch_count' => $this->getBranchCount($repo),
            'tags_count' => $this->getTagsCount($repo),
            'refs' => $refs,
            'active' => 'source',
            'parts' => $this->getPartsWithName($repository_name,""),
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

        $repo = new \Git2\Repository($path);
        $absolute_refs = $this->normalizeReference($repo,$refs);

        $ref = \Git2\Reference::lookup($repo, $absolute_refs);
        $ref = $ref->resolve();

        $commit = $repo->lookup($ref->getTarget());
        if ($commit instanceof Git2\Tag) {
            $commit = $commit->getTarget();
        }
        $tree = $commit->getTree();

        $tree = $tree->getSubTree($name);

        $dirname = $name . "/";

        $meta = array();
        foreach ($tree as $entry) {
            $commit_id = trim(`GIT_DIR={$path} git log {$ref->getName()} --format=%H -n1 -- {$dirname}{$entry->name}`);
            $meta[$entry->name] = $repo->lookup($commit_id);
        }

        $entry = $tree->getEntryByName("README.md");

        $data = '';
        if ($entry) {
            $blob = $repo->lookup($entry->oid);
            $sd = new \Sundown($blob->getContent());
            $data = $sd->toHtml();
        }

        $template = (isset($_REQUEST['_pjax'])) ?
            'ChobieGit2RepositoryBrowserBundle:Default:pjax.tree.html.twig':
            'ChobieGit2RepositoryBrowserBundle:Default:tree.html.twig';


        return $this->render($template, array(
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
            'active' => 'source',
            'parts' => $this->getPartsWithName($repository_name, $name),
        ));

    }

    protected function getPartsWithName($repository_name, $name)
    {
        $parts = array(array(
            "name" => pathinfo($repository_name,\PATHINFO_FILENAME),
            "path" => "",
        ));

        $stack = array();
        foreach (explode("/", $name) as $part) {
            $stack[] = $part;
            $parts[] = array(
                "name" => $part,
                "path" => join("/", $stack),
            );
        }


        return $parts;
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

    /**
     * show commit logs
     *
     * @param $repository_name
     * @param $refs
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function commitsAction($repository_name, $refs)
    {
        $repo = $this->getRepository($repository_name);
        $request = $this->getRequest();
        $next = null;

        if ($refs != "HEAD") {
            $refs = $this->normalizeReference($repo,$refs);
        }

        try {
            $ref = \Git2\Reference::lookup($repo, $refs);
            $ref = $ref->resolve();
            $commit = $repo->lookup($ref->getTarget());
            if ($commit instanceof Git2\Tag) {
                $commit = $commit->getTarget();
            }

            $walker = new Git2\Walker($repo);

            $oid = $request->query->getAlnum('next') ? $request->query->getAlnum('next') : $commit->getOId();
            $walker->push($oid);

            $i= 0 ;
            $commits = array();
            foreach($walker as $tmp) {
                if ($i>21) break;

                $commits[] = $tmp;
                $i++;
            }
            $next = array_pop($commits);
            $next = $next->getOid();
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
            'active' => 'commit',
            'next' => $next
        ));
    }

    public function commitAction($repository_name, $commit_id)
    {
        $repo = $this->getRepository($repository_name);

        $n_commit  = escapeshellarg($commit_id);
        $repository_path = $this->getRepositoryPath($repository_name);
        $commit = $repo->lookup($commit_id);

        $stat = `GIT_DIR={$repository_path} git log -p {$n_commit} -n1 -m`;
        $struct = Diff\Parser::parse($stat);

        $commit = $repo->lookup($commit_id);
        $template = (isset($_REQUEST['_pjax'])) ?
            'ChobieGit2RepositoryBrowserBundle:Default:pjax.commit.html.twig':
            'ChobieGit2RepositoryBrowserBundle:Default:commit.html.twig';

        return $this->render($template, array(
            'repository_name' => $repository_name,
            'commit' => $commit,
            'diff' => $struct,
            'branch_count' => $this->getBranchCount($repo),
            'tags_count' => $this->getTagsCount($repo),
            'refs'  => "HEAD",
            'active' => 'commit',
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
            'active' => 'branches',
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
            'active' => 'tags',
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
            'active' => 'source',
        ));
    }


    /**
     * @param $repository
     * @return \Git2\Reference
     */
    public function getDefaultReference($repository)
    {
        $ref = \Git2\Reference::lookup($repository, "HEAD");
        $ref = $ref->resolve();
        return $ref;
    }

    public function normalizeReference($repository, $refname)
    {
        $array = \Git2\Reference::each($repository,null,function($name) use ($refname) {
            return preg_match("/{$refname}/",$name);
        });
        if (count($array)) {
            $ref = array_shift($array);
            if ($ref) {
                return $ref->getName();
            }
        }

        return false;
    }

}
