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

class VersionSorter
{
	private static function scan_state_get($c)
	{
		$c = (string)$c;
		if (ctype_digit($c)) {
			return 0;
		} else if (ctype_alpha($c)) {
			return 1;
		} else {
			return 2;
		}
	}

	private static function parse_version_word($vsi)
	{
		$start = $end = $size =0;
		$max = strlen($vsi);
		$res = array();
		while($start < $max) {
			$current_state = self::scan_state_get($vsi[$start]);
			if ($current_state == 2) {
				$start++;
				$end = $start;
				continue;
			}
				
			do {
				$end++;
				$next_char = @$vsi[$end];
				$next_state = self::scan_state_get($next_char);
			} while($current_state == $next_state);
			$size = $end - $start;
			$res[] = substr($vsi,$start,$size);
				
			$start = $end;
		}
		return $res;
	}

	public static function compare_by_version($a, $b)
	{
		return strcmp($a,$b);
	}


	public static function sort($array)
	{
		$widest = 0;
		$result = array();
		foreach($array as $item) {
			$vsi = self::parse_version_word($item);
			foreach($vsi as $it) {
				$tmp = strlen($it);
				if($widest < $tmp) {
					$widest = $tmp;
				}
			}
			$result[$item] =$vsi;
		}

		$normalized = array();
		foreach($result as $key => $item) {
			foreach($item as $b) {
				$length = strlen($b);
				if(ctype_digit((string)$b[0])) {
					for($i=0;$i<$widest - $length;$i++) {
						@$normalized[$key] .= ' ';
					}
				}
				@$normalized[$key] .= $b;
				if(ctype_alpha((string)$b[0])) {
					for($i=0;$i<$widest - $length;$i++) {
						@$normalized[$key] .= ' ';
					}
				}

			}
		}
		asort($normalized);
		return array_keys($normalized);
	}

	public static function rsort($array)
	{
		$widest = 0;
		$result = array();
		foreach($array as $item) {
			$vsi = self::parse_version_word($item);
			foreach($vsi as $it) {
				$tmp = strlen($it);
				if($widest < $tmp) {
					$widest = $tmp;
				}
			}
			$result[$item] =$vsi;
		}

		$normalized = array();
		foreach($result as $key => $item) {
			foreach($item as $b) {
				$length = strlen($b);
				if(ctype_digit((string)$b[0])) {
					for($i=0;$i<$widest - $length;$i++) {
						@$normalized[$key] .= ' ';
					}
				}
				@$normalized[$key] .= $b;
				if(ctype_alpha((string)$b[0])) {
					for($i=0;$i<$widest - $length;$i++) {
						@$normalized[$key] .= ' ';
					}
				}

			}
		}
		arsort($normalized);
		return array_keys($normalized);
	}
}
