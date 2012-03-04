<?php

/**
 * This file is part of the KwattroMarkdownBundle package.
 *
 * (c) Christophe Willemsen <willemsen.christophe@gmail.com>
 *
 * Released under the MIT License.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that is bundled with this package.
 */

namespace Chobie\Bundle\Git2RepositoryBrowserBundle\Twig\Extension;


class ChobieGit2RepositoryBrowserBundleExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'chobie_git2_repository_browser';
    }

    public function getFilters()
    {
        return array(
            'md5' => new \Twig_Filter_Method($this, 'md5'),
            'truncate' => new \Twig_Filter_Method($this, 'truncate'),
            'markdown' => new \Twig_Filter_Method($this, 'markdown', array('is_safe'=>array('html'))),
        );

    }

    public function markdown($string)
    {
        $sd = new \Sundown\Markdown(new AlbinoWithHtml(array('autolink'=>true)), array("fenced_code_blocks"=>true));
        return $sd->render($string);
    }

    public function truncate($value, $length = 30, $preserve = false, $separator = '...')
    {
        if (mb_strlen($value, 'utf8') > $length) {
            if ($preserve) {
                if (false !== ($breakpoint = mb_strpos($value, ' ', $length, 'utf8'))) {
                    $length = $breakpoint;
                }
            }

            return mb_substr($value, 0, $length, 'utf8') . $separator;
        }

        return $value;
    }

    public function md5($string)
    {
        return md5($string);
    }
}

class AlbinoWithHtml extends \Sundown\Render\HTML
{
    public function block_code($code, $language)
    {
        return \Chobie\Bundle\Git2RepositoryBrowserBundle\Util\Albino::colorize($code, $language);
    }
}