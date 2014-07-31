<?php

include 'helper.php';

if (isset($_FILES['file'])) {

	$temp_dir = sys_get_temp_dir();
	$part_prefix = basename($_FILES['file']['name']);
	$total_size = filesize($_FILES['file']['tmp_name']);

	$stmt = array();
	$part = array();

	// 1. Split big file into statements
	$fp = fopen($_FILES['file']['tmp_name'], 'r');
	while (($line = fgets($fp)) !== false) {
		if (count($stmt) == 0 || strpos($line, 'INSERT INTO') === 0) {
			$stmt[] = $stmt_file = tempnam($temp_dir, 'stmt');
		}
		file_put_contents($stmt_file, $line, FILE_APPEND);
	}
	fclose($fp);

	// 2. Join statements into Nth files
	$stmt_file = reset($stmt);
	foreach (distribute($total_size, $_POST['parts']) as $part_index => $part_size) {
		$part[] = $part_file = tempnam($temp_dir, 'part');
		for ($written = 0; $stmt_file && $written < $part_size; $written += filesize($stmt_file), $stmt_file = next($stmt)) {
			file_put_contents($part_file, file_get_contents($stmt_file), FILE_APPEND);
		}
	}

	// 3. Add all parts into archive
	$zip_file = tempnam($temp_dir, 'zip');
	$zip = new ZipArchive();
	$zip->open($zip_file);
	foreach ($part as $part_index => $part_file) {
		$part_no = $part_index + 1;
		$zip->addFile($part_file, "part-$part_no.sql");
	}
	$zip->close();

	// 4. Cleanup
	array_map('unlink', $stmt);
	array_map('unlink', $part);

	header('Content-Type:  application/zip');
	header('Content-Length: '.filesize($zip_file));
	header("Content-Disposition: attachment; filename=\"$part_prefix.zip\"");
	echo file_get_contents($zip_file);

	unlink($zip_file);
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>.sql split &mdash; Split .sql file into several parts</title>
	<meta name="description" content="Split one big .sql file into several smaller .sql files">
	<meta name="keywords" content="sql,split,smaller,part">
	<meta name="author" content="Vladimir Barbarosh">
	<link href="lib/bootstrap-3.2.0-dist/css/bootstrap.min.css" type="text/css" rel="stylesheet">
	<link href="cover.css" type="text/css" rel="stylesheet">
</head>
<body>

<div class="site-wrapper">
<div class="site-wrapper-inner">

	<div class="cover-container">

		<div class="masthead clearfix">
			<div class="inner">
				<h3 class="masthead-brand">.sql split</h3>
				<ul class="nav masthead-nav">
					<li><a href="mailto:vladimir.barbarosh@gmail.com?subject=.sql+split">Contact</a></li>
				</ul>
			</div>
		</div>

		<form id="form" enctype="multipart/form-data" method="POST" class="inner cover">
			<h1 class="cover-heading">Split .sql file into <input type="text" name="parts" value="5"> parts</h1>
			<p class="lead"><a id="select_sql_file" href="#" class="btn btn-lg btn-default">Select .sql file</a></p>
			<input type="file" name="file" id="file" class="hidden">
		</form>

		<div class="mastfoot">
			<div class="inner">
				<p>Cover template for <a href="http://getbootstrap.com">Bootstrap</a>, by <a href="https://twitter.com/mdo">@mdo</a>.</p>
			</div>
		</div>

	</div>

</div>
</div>

<script src="lib/jquery-1.11.1.min.js" type="text/javascript"></script>
<script src="lib/bootstrap-3.2.0-dist/js/bootstrap.min.js" type="text/javascript"></script>
<script type="text/javascript">
jQuery(function ($) {

	$(document).on('change', '#file', function () {
		$('#form').submit();
		$('#file').replaceWith($('#file').clone());
	});

	$('#select_sql_file').click(function (event) {
		event.preventDefault();
		$('#file').click();
	});

});
</script>

</body>
</html>
