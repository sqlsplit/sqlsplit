<?php

include 'helper.php';

if (isset($_FILES['file'])) {
    call_user_func(function () {

        $free_resources = array();

        $file_name = basename($_FILES['file']['name'], '.zip');
        $file_size = filesize($_FILES['file']['tmp_name']);
        $parts = $_POST['parts'];

        $fp = false;
        $zip = new ZipArchive();
        if ($zip->open($_FILES['file']['tmp_name']) === true) {
            $fp = $zip->getStream($zip->getNameIndex(0));
            if ($fp === false) {
                $zip->close();
            }
            else {
                $stat = $zip->statIndex(0);
                $file_size = $stat['size'];
                $free_resources[] = bind('fclose', $fp);
                $free_resources[] = bind(array($zip, 'close'));
            }
        }
        else {
            // trigger_error('$zip->open failed: '.$zip->getStatusString(), E_USER_ERROR);
        }

        if ($fp === false) {
            $fp = fopen($_FILES['file']['tmp_name'], 'r');
            $free_resources[] = bind('fclose', $fp);
        }

        $zip_file = sql_split($fp, $file_size, $parts);
        $free_resources[] = bind('unlink', $zip_file);

        header('Content-Type:  application/zip');
        header('Content-Length: '.filesize($zip_file));
        header("Content-Disposition: attachment; filename=\"$file_name.zip\"");
        echo file_get_contents($zip_file);

        array_map('call_user_func', $free_resources);
        exit;
    });
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
