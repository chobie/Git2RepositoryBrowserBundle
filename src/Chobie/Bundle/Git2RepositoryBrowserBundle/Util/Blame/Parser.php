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

namespace Chobie\Bundle\Git2RepositoryBrowserBundle\Util\Blame;

class Parser
{
	public static function parse($string)
	{
		$file = new File();
		$lines = explode("\n",$string);

		$meta   = array();
		$result = array();
		$commits = array();
		
		foreach($lines as $line) {
			if(preg_match("/^(?P<commit_id>[a-zA-Z0-9]{40})\s(?P<old>\d+)\s(?P<new>\d+)(?P<group>\s\d+)?$/",$line,$match)) {
				$commit_id = $match['commit_id'];
				$old   = (int)$match['old'];
				$new   = (int)$match['new'];
				
				if(isset($match['group'])){
					$continuous = $match['group'];
				} else {
					$continuous = null;
				}
				
				if (!isset($commits[$commit_id])) {
					$commits[$commit_id] = new Commit($commit_id);
					$file->addCommit($commits[$commit_id]);
				}
				
				if ($continuous > 0) {
					$meta[$commit_id] = array();
					$group = new Group($continuous);
					$group->setCommitId($commit_id);
					$file->addGroup($group);
					
					$result[$commit_id] = true;
				}
			} else {
				if(strpos($line,"\t") === 0) {
					list(, $value) = explode("\t",$line,2);
					$result[] = array(
						"commit_id" => $commit_id,
						"lines" => $value
					);
					$group->add(new Line($old,$new, $value));
				} else if($line == '') {
				} else if($line == 'boundary') {
				} else {
					list($key, $value) = explode(" ",$line,2);
					$commits[$commit_id]->add($key,$value);
				}
			}
		}
		
		return $file;
		return $result;
	}
}
