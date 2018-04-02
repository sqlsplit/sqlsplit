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

function bind($cb)
{
    $args = func_get_args();
    array_shift($args);
    return function () use ($cb, $args) {
        return call_user_func_array($cb, $args);
    };
}

function temp_dir($cb)
{
    try {
        for ($attempt = 1; $attempt <= 5; ++$attempt) {
            $temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . sha1(secure_rand(128));
            if (file_exists($temp_dir)) {
                unset($temp_dir);
            }
            else {
                mkdir($temp_dir);
                break;
            }
        }
        if (isset($temp_dir)) {
            call_user_func($cb, $temp_dir);
        }
        else {
            throw new Exception("temp_dir: could not create temporary directory after $attempt attempt(s)");
        }
    }
    catch (Exception $exception) {
    }

    if (isset($temp_dir)) {
        rmdir_r($temp_dir);
    }

    if (isset($exception)) {
        throw $exception;
    }
}

// Strong cryptography in PHP
// http://www.zimuel.it/en/strong-cryptography-in-php/
// > Don't use rand() or mt_rand()
function secure_rand($length)
{
    if (function_exists('openssl_random_pseudo_bytes')) {
        $rnd = openssl_random_pseudo_bytes($length, $strong);
        if ($strong) {
            return $rnd;
        }
    }
    $sha ='';
    $rnd ='';
    if (file_exists('/dev/urandom')) {
        $fp = fopen('/dev/urandom', 'rb');
        if ($fp) {
            if (function_exists('stream_set_read_buffer')) {
                stream_set_read_buffer($fp, 0);
            }
            $sha = fread($fp, $length);
            fclose($fp);
        }
    }
    for ($i = 0; $i < $length; $i++) {
        $sha = hash('sha256', $sha.mt_rand());
        $char = mt_rand(0, 62);
        $rnd .= chr(hexdec($sha[$char].$sha[$char+1]));
    }
    return $rnd;
}

function rmdir_r($dir)
{
    if (is_file($dir)) {
        unlink($dir);
        return;
    }

    foreach (scandir($dir) as $file) {
        if ($file != '.' && $file != '..') {
            rmdir_r("$dir/$file");
        }
    }

    rmdir($dir);
}

function sql_split($fp, $file_size, $parts_count)
{
    $zip_file = tempnam(sys_get_temp_dir(), 'zip');

    temp_dir(function ($d) use ($zip_file, $fp, $file_size, $parts_count) {

        $statements = array();
        $parts = array();

        // split file into statements
        while (($line = fgets($fp)) !== false) {
            if (count($statements) == 0 || strpos($line, 'INSERT INTO') === 0) {
                $statements[] = $stmt_file = tempnam($d, 'stmt');
            }
            file_put_contents($stmt_file, $line, FILE_APPEND);
        }

        // join statements into parts
        $stmt_file = reset($statements);
        foreach (distribute($file_size, $parts_count) as $part_index => $part_size) {
            $parts[] = $part_file = tempnam($d, 'part');
            for ($written = 0; $stmt_file && $written < $part_size; $written += filesize($stmt_file), $stmt_file = next($statements)) {
                file_put_contents($part_file, file_get_contents($stmt_file), FILE_APPEND);
            }
        }

        // pack all parts into archive
        $zip = new ZipArchive();
        $zip->open($zip_file);
        foreach ($parts as $part_index => $part_file) {
            $part_no = $part_index + 1;
            $zip->addFile($part_file, "part-$part_no.sql");
        }
        $zip->close();

    });

    return $zip_file;
}
