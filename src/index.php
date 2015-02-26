<?php

include 'helper.php';

if (isset($_FILES['file'])) {

    $free_resources = array();

	$temp_dir = sys_get_temp_dir();
	$part_prefix = basename($_FILES['file']['name'], '.zip');
	$total_size = filesize($_FILES['file']['tmp_name']);

	$stmt = array();
	$part = array();

    $fp = false;
    $zip_in = new ZipArchive();
    if ($zip_in->open($_FILES['file']['tmp_name']) === true) {
        $fp = $zip_in->getStream($zip_in->getNameIndex(0));
        if ($fp === false) {
            $zip_in->close();
        }
        else {
            $stat = $zip_in->statIndex(0);
            $total_size = $stat['size'];
            $free_resources[] = bind('fclose', $fp);
            $free_resources[] = bind(array($zip_in, 'close'));
        }
    }
    else {
        // trigger_error('$zip->open failed: '.$zip->getStatusString(), E_USER_ERROR);
    }

    if ($fp === false) {
        $fp = fopen($_FILES['file']['tmp_name'], 'r');
        $free_resources[] = bind('fclose', $fp);
    }

	// 1.
	//
	//  big file into statements
	while (($line = fgets($fp)) !== false) {
		if (count($stmt) == 0 || strpos($line, 'INSERT INTO') === 0) {
			$stmt[] = $stmt_file = tempnam($temp_dir, 'stmt');
            $free_resources[] = bind('unlink', $stmt_file);
		}
		file_put_contents($stmt_file, $line, FILE_APPEND);
	}

	// 2. Join statements into Nth files
	$stmt_file = reset($stmt);
	foreach (distribute($total_size, $_POST['parts']) as $part_index => $part_size) {
		$part[] = $part_file = tempnam($temp_dir, 'part');
        $free_resources[] = bind('unlink', $part_file);
		for ($written = 0; $stmt_file && $written < $part_size; $written += filesize($stmt_file), $stmt_file = next($stmt)) {
			file_put_contents($part_file, file_get_contents($stmt_file), FILE_APPEND);
		}
	}

	// 3. Add all parts into archive
	$zip_file = tempnam($temp_dir, 'zip');
	$zip_out = new ZipArchive();
    $zip_out->open($zip_file);
	foreach ($part as $part_index => $part_file) {
		$part_no = $part_index + 1;
        $zip_out->addFile($part_file, "part-$part_no.sql");
	}
    $zip_out->close();

	// 4. Cleanup
	// array_map('unlink', $stmt);
	// array_map('unlink', $part);
    array_map('call_user_func', $free_resources);

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
	<link href="css/cover.css" type="text/css" rel="stylesheet">
	<link href="css/bootstrap.css" type="text/css" rel="stylesheet">
</head>
<body>

<div class="site-wrapper">
<div class="site-wrapper-inner">

	<div class="cover-container">

		<div class="masthead clearfix">
			<div class="inner">
				<h3 class="masthead-brand">.sql split</h3>
			</div>
		</div>

		<form id="form" enctype="multipart/form-data" method="POST" class="inner cover">
            <input type="file" name="file" id="file" class="hidden">
            <h1 class="cover-heading">
                <label style="font-weight: normal;">Split .sql<span class="text-muted">[.zip]</span> file into <input type="text" name="parts" value="5"> parts</label>
            </h1>
            <br/>
			<p class="lead">
                <a id="select_sql_file" href="#" class="btn btn-lg btn-default">Select a file</a>
            </p>
		</form>

		<div class="mastfoot">
			<div class="inner">
				<p><a href="https://github.com/sqlsplit/sqlsplit">source code</a> | <a href="http://sqlsplit.uservoice.com">feedback</a></p>
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

<script type="text/javascript">
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-53400667-1', 'auto');
  ga('send', 'pageview');
</script>

</body>
</html>
