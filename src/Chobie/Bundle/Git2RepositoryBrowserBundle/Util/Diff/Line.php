<?php
/*
 * The MIT License
 *
 * Copyright (c) 2011 Shuhei Tanuma
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

namespace Chobie\Bundle\Git2RepositoryBrowserBundle\Util\Diff;

class Line
{
	const NEUTRAL = 0;
	const ADD = 1;
	const REMOVE = -1;
	
	protected $line;
	protected $old;
	protected $new;
	protected $status;
	
	public function __construct($line)
	{
		$this->line = $line;
		
		$char = $line[0];
		if ($char == "+") {
			$this->setStatus(self::ADD);
		} elseif ($char == "-") {
			$this->setStatus(self::REMOVE);
		} else {
			$this->setStatus(self::NEUTRAL);
		}
	}
	
	public function setStatus($status)
	{
		$this->status = $status;
	}
	
	public function isAdded()
	{
		return $this->status == self::ADD;
	}
	
	public function isRemoved()
	{
		return $this->status == self::REMOVE;
	}
	
	public function isNeutral()
	{
		return $this->status == self::NEUTRAL;
	}
	
	public function getLine()
	{
		return $this->line;
	}
	
	public function diff(Line $line)
	{
		$a = mb_substr($this->getLine(),1);
		$b = mb_substr($line->getLine(),1);
		
		$mark = mb_substr($this->getLine(),0,1);
		
		$prefix = $this->commonPrefix($a,$b);
		$suffix = $this->commonSuffix($a,$b);

		$prefix_str = mb_substr($a,0,$prefix);
		$mid_str    = mb_substr($a,$prefix,mb_strlen($a)-$suffix-$prefix);
		$suffix_str = mb_substr($a,mb_strlen($a)-$suffix);
		
		return array(
			'prefix' => $mark . $prefix_str,
			'unique' => $mid_str,
			'suffix' => $suffix_str
			);
	}

	protected function commonPrefix($text1,$text2)
	{
		if (!$text1 || !$text2 || mb_substr($text1,0,1) != mb_substr($text2[0],0,1)) {
			return 0;
		}
		
		$n = min(mb_strlen($text1),mb_strlen($text2));
		
		for ($i=0;$i<$n;$i++) {
			if (mb_substr($text1,$i,1) != mb_substr($text2,$i,1)) {
				return $i;
			}
		}
		return $n;
	}

	protected function commonSuffix($text1,$text2)
	{
		if (!$text1 || !$text2 || mb_substr($text1,0,1) != mb_substr($text2[0],0,1)) {
			return 0;
		}
		
		$text1_length = mb_strlen($text1);
		$text2_length = mb_strlen($text2);
		
		$n = min($text1_length,$text2_length);
		
		for ($i=1;$i<=$n;$i++) {
			if (mb_substr($text1,$text1_length-$i,1) != mb_substr($text2,$text2_length-$i,1)) {
				return $i-1;
			}
		}
		return $n;
	}
	
	public function setOldNumber($number)
	{
		$this->old = $number;
	}
	
	public function getOldNumber()
	{
		return $this->old;
	}
	
	public function setNewNumber($number)
	{
		$this->new = $number;
	}
	
	public function getNewNumber()
	{
		return $this->new;
	}
}