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

class Lines implements \Iterator
{
	protected $position = 0;
	protected $lines = array();
	
	public function add(Line $line)
	{
		$this->lines[] = $line;
	}
	
	public function current()
	{
		return $this->lines[$this->position];
	}
	
	public function key()
	{
		return $this->position;
	}
	
	public function next()
	{
		++$this->position;
	}
	
	public function rewind()
	{
		$this->position = 0;
	}
	
	public function valid()
	{
		return isset($this->lines[$this->position]);
	}
	
	
	public function hasPrevious()
	{
		if (isset($this->lines[$this->position-1])) {
			return true;
		}
		return false;
	}
	
	public function hasNext()
	{
		if (isset($this->lines[$this->position+1])) {
			return true;
		}
		return false;
	}
	
	public function getNext()
	{
		if ($this->hasNext()) {
			return $this->lines[$this->position+1];
		}
	}
	
	public function getPrevious()
	{
		if ($this->hasPrevious()) {
			return $this->lines[$this->position-1];
		}
	}
}