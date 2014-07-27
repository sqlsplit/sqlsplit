<?php

$part = 1;
foreach (file('a.sql') as $line) {
	if (strpos($line, 'INSERT INTO') === 0) {
		$part += 1;
	}
	file_put_contents(sprintf('part-%03d.sql', $part), $line, FILE_APPEND);
}
