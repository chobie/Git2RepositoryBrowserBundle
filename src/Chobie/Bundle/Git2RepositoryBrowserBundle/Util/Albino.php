<?php
/*
 * The MIT License
 *
 * Copyright (c) 2011 - 2012 Shuhei Tanuma
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Chobie\Bundle\Git2RepositoryBrowserBundle\Util;

class Albino
{
    const VERSION = '1.0';
    const DEFAULT_ENCODING = 'utf8';

    protected $bin = 'pygmentize';
    protected $target;
    protected $options = array();
    protected $encoding;

    /**
     * create Albino instance.
     *
     * @param string $target string which you want convert with pygmentize
     * @param string $lexer
     * @param string $format
     * @param string $encoding
     */
    public function __construct($target, $lexer = "text", $format = "html", $encoding = self::DEFAULT_ENCODING)
    {
        $this->target = $target;
        $this->options = array('l'=>$lexer,'f'=>$format,'O'=> "encoding=$encoding");
        $this->encoding = $encoding;
    }

    /**
     * Convention method of pygmentize
     *
     * @param string $target string which you want convert with pygmentize
     * @param string $lexer
     * @param string $format
     * @param string $encoding
     * @return string pygmentized string.
     */
    public static function colorize($target, $lexer = "text", $format = "html", $encoding = self::DEFAULT_ENCODING)
    {
        $albino = new self($target,$lexer,$format,$encoding);

        $result = $albino->execute();
        unset($albino);
        return $result;
    }

    /**
     * convert array options to pygmentize option
     *
     * @todo check specified options is valid
     * @param array $options
     */
    private function convertOptions($options)
    {
        $result = array();
        foreach ($options as $key => $value) {
            $result[] = "-$key $value";
        }
        return join(" ", $result);
    }

    /**
     * execute pygmentize on this process.
     *
     * @param array $options pygmentize options
     * @throws Exception
     * @return string pygmentized string
     */
    private function execute($options = array())
    {
        $result = false;
        if (!$options) {
            $options = $this->options;
        }
        $converted_options = $this->convertOptions($options);
        $descriptorspec = array(
            0 => array("pipe","r"),
            1 => array("pipe","w")
        );
        $process = proc_open("{$this->bin} {$converted_options}",$descriptorspec,$pipe);
        if (is_resource($process)) {
            fwrite($pipe[0],$this->target);
            fclose($pipe[0]);

            $result = stream_get_contents($pipe[1]);
            fclose($pipe[1]);
        } else {
            throw new Exception("could not execute {$this->bin}.");
        }
        return $result;
    }
}