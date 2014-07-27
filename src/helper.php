<?php

# разделить $value раномерно между $parts частями
#
# если $parts представляет собой массив
# 1. представим каждый элемент массива как гору. чем выше
#    значение, тем выше гора.
# 2. пока есть единицы
#	определить самую маленькую горку (их список)
#	добавить единицу к одной из них
#
function distribute($value, $parts)
{
	if (is_array($parts)) {
		$a = $parts;
		for ($i = $value; $i > 0; --$i) {
#				$i_min = null;
#				foreach ($a as $key => $val) {
#					if (!isset($i_min) || ($a[$key] < $a[$i_min])) {
#						$i_min = $key;
#					}
#				}
#				$a[$i_min] += 1;
			$min = null;
			$v_min = 0;
			foreach ($a as $k => $v) {
				if ($min == null || $v < $v_min) {
					$min = array($k);
					$v_min = $v;
					continue;
				}
				if ($v == $v_min) {
					$min[] = $k;
				}
			}
			$a[$min[rand(0, count($min)-1)]] += 1;

		}
		return $a;
	}

	$a = array();
	foreach (range(1, $parts) as $v) {
		$a[] = floor($value/$parts);
	}
	$b = array_keys($a);
	shuffle($b);
	for ($rem = $value % $parts; $rem > 0; --$rem) {
		$a[array_shift($b)] += 1;
	}
	return $a;
}
