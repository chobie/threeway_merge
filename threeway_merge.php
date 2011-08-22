<?php
function threeway_merge($left, $right, $origin)
{
	$a = hash_diff($origin, $left, true);
	$b = hash_diff($origin, $right,true);
	
	$c = array_merge_recursive($a,$b);
	foreach ($c as $key => $items) {
		switch ($key) {
			case "-":
				threeway_merge_remove($items, $origin);
				break;
			case "+":
				threeway_merge_add($items, $origin);
				break;
		}
	}
	
	return $origin;
}

function threeway_merge_add($items, &$origin)
{
	foreach($items as $key => $value) {
		if(is_array($value)) {
			$tmp = &$origin[$key];
			threeway_merge_add($items[$key],$tmp);
		} else {
			$origin[$key] = $value;
		}
	}
}

function threeway_merge_remove($items, &$origin)
{
	foreach($items as $key => $value) {
		if(is_array($value)) {
			$tmp = &$origin[$key];
			threeway_merge_remove($items[$key],$tmp);
		} else {
			unset($origin[$key]);
		}
	}
}

function hash_diff($array1, $array2, $strict = false) {
	$diff = array();
	$is_hash = determine_array_type($array1);

	if ($is_hash) {
		foreach ($array1 as $key => $value) {
			if (!array_key_exists($key,$array2)) {
				$diff['-'][$key] = $value;
			} elseif (is_array($value)) {
				if (!is_array($array2[$key])) {
					$diff['-'][$key] = $value;
					$diff['+'][$key] = $array2[$key];
				} else {
					$new = hash_diff($value, $array2[$key], $strict);
					if ($new !== false) {
						if (isset($new['-'])){
							$diff['-'][$key] = $new['-'];
						}
						if (isset($new['+'])){
							$diff['+'][$key] = $new['+'];
						}
					}
				}
			} elseif ($strict && $array2[$key] != $value) {
				$diff['-'][$key] = $value;
				$diff['+'][$key] = $array2[$key];
			} elseif ($strict && $array2[$key] == $value) {
				/** nothing to do */
			} elseif (!$strict && $array2[$key] != $value) {
				/** nothing to do */
			} elseif (!$strict && $array2[$key] == $value) {
				/** nothing to do */
			} else {
				throw new Exception('unexpected type');
			}
		}

		foreach ($array2 as $key => $value) {
			if ($is_hash && !array_key_exists($key,$array1)) {
				$diff['+'][$key] = $value;
			}
		}
	} else {
		$tmp = array_diff($array1,$array2);
		foreach($tmp as $item) {
			$diff['-'][] = $item;
		}
		$tmp = array_diff($array2,$array1);
		foreach($tmp as $item) {
			$diff['+'][] = $item;
		}
	}

	return $diff;
}

function determine_array_type($array)
{
	if((bool)$array) {
		$idx = 0;
		foreach($array as $key => $value) {
			if(is_string($key)){
				return 1;
			} else {
				if ($key != $idx) {
					return 1;
				}
			}
			$idx++;
		}
	}

	return 0;
}
