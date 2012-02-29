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

class Hunk{
	protected $old;
	protected $new;
	protected $info;
	protected $summary;
	protected $raw_body;
	protected $body;
	protected $lines;

	protected $added = 0;
	protected $removed = 0;
	
	
	public function getStat()
	{
		return array(
			'added' => $this->added,
			'removed' => $this->removed
			);
	}
	
	public function getSummary()
	{
		return $this->summary;
	}
	
	public function setSummary($summary)
	{
		$this->summary = $summary;
	}
	
	public function setInfo($info)
	{
		$this->info = $info;
	}
	
	public function setOld($old)
	{
		$this->old = $old;
	}
	
	public function setNew($new)
	{
		$this->new = $new;
	}
	public function setBody(array $bodies)
	{
		$this->added = 0;
		$this->removed = 0;
		
		$this->body = $bodies;
		$this->lines = new Lines();
		
		$o = $this->old[0];
		$n = $this->new[0];
		
		foreach ($bodies as $body) {
			if ($body->isAdded()) {
				$this->added++;
				$body->setNewNumber($n);
				$n++;
			} else if ($body->isRemoved()) {
				$this->removed++;
				$body->setOldNumber($o);
				$o++;
			} else {
				$body->setNewNumber($n);
				$body->setOldNumber($o);
				$n++;
				$o++;
			}
			$this->raw_body .= $body->getLine() . PHP_EOL;;
			$this->lines->add($body);	
		}
		
		
	}
	
	public function getCurrentFileName(){}
	
	public function getRawBody()
	{
		return $this->raw_body;
	}
	
	public function getLines()
	{
		return $this->lines;
	}
}
