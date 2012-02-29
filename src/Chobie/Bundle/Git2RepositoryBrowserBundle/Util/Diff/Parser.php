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

class Parser
{
	public function __construct()
	{
	}
	
	public static function parse($string)
	{
		$lines = preg_split("/\r?\n/",$string);
		$status = 0;
		$struct = new Struct();

		$count = count($lines);
		for($i=0;$i<$count;$i++) {
			$line = $lines[$i];
			if($status == 0) {
				if(strpos($line,'diff') === 0) {
					$mini = array();
					$status = 1;
				}
			} else if($status == 1){
				if(strpos($line,'@@') !== 0) {
					$header = array();
					do{
						list($key,$value) = explode(" ",$lines[$i],2);
						if ($key == "---") {
							$key = "old";
						} else if ($key == "+++") {
							$key = "new";
						}

						$mini['header'][$key] = $value;
						$i++;
					} while(strpos($lines[$i],'@@') !== 0 && $i<$count);
					$file = new File();
					$file->setHeaders($mini['header']);
					$struct->addFile($file);

					$status = 2;
					$i--;
				}
			} else if ($status == 2){
				while(isset($lines[$i]) && strpos($lines[$i],'@@') === 0){
					$hunk = new Hunk();
					if (preg_match("/@@\s+-(?P<old>\d+(?:,\d*)?)\s*\+(?P<new>\d+(?:,\d*)?)\s*@@(?P<info>.*)?/",$lines[$i],$match)) {
						$mini['summary'] = $lines[$i];
						$old = explode(",",$match['old']);
						$new = explode(",",$match['new']);
						$info = $match['info'];
						
						$mini['old']  = $old;
						if (!isset($mini['old'][1])) {
							$mini['old'][1] = $mini['old'][0];
						}
						$hunk->setOld($mini['old']);

						$mini['new']  = $new;
						if (!isset($mini['new'][1])) {
							$mini['new'][1] = $mini['new'][0];
						}
						$hunk->setNew($mini['new']);
						$hunk->setInfo($info);
						$hunk->setSummary($mini['summary']);
						
						$mini['info'] = $info;
						$i++;
					}
					
					$offset_a = 0;
					$offset_b = 0;
					$memo = $i;
					while($offset_a <= $mini['old'][1] && $offset_b < $mini['new'][1]){
						$tmp = $lines[$i];
						if ($tmp[0] == "-") {
							$offset_a++;
						} else if ($tmp[0] == "+"){
							$offset_b++;
						} else {
							$offset_a++;
							$offset_b++;
						}
						$i++;
					}
					$buf = array();
					for($t=$memo;$t<$i;$t++){
						$buf[] = new Line($lines[$t]);
					}
					$mini['body'] = $buf;
					$hunk->setBody($buf);
					$file->addHunk($hunk);
				}
				$i--;
				$status = 0;
			}
		}
		return $struct;
	}
}
