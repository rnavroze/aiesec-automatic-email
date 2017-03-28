<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Entity Email Configuration</title>
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width">
	<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400italic,400,600" rel="stylesheet"
	      type="text/css">
	<link href="res/style.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="header_bar">
	<img src="res/logo.svg" class="aiesec_logo">
	<ul>
		<li><a href="#">Opportunities</a></li>
		<li><a href="#">People</a></li>
		<li><a href="#">Organizations</a></li>
		<li><a href="#">Analytics</a></li>
		<li><a href="#">Committees</a></li>
	</ul>
	<div class="user_avatar">
		<a href="#">
			<img src="res/avatar.png">
		</a>
	</div>
</div>
<div class="frame">
	<?=isset($_GET['success']) ? "The file has been uploaded successfully.<br><br>" : ""?>
	<div style="font-size: 2em; text-align: center;">Entity Email Configuration</div>
	<i>In the HTML template, %EPNAME% will be replaced with the EP's name.</i>
	<form action="upload.php" method="post" enctype="multipart/form-data">
		Entity: <select name="entity">
			<?php
			// TODO: Ideally, we should be caching this, but for the purpose of demonstration it's not necessary.
			require "libraries/expa/expa.php";
			$expa = new EXPA();
			$mclist = $expa->call("lists/mcs");
			foreach ($mclist as $mc)
				echo "<option value='{$mc['id']}'>{$mc['name']}</option>\n"
			?>
		</select><br>
		HTML file: <input type="file" name="html_file" id="html_file" accept="*.html"><br>
		Status:
		<select name="status">
			<option value="open">Open</option>
			<option value="applied">Applied</option>
			<option value="accepted">Accepted</option>
			<option value="approved">Approved</option>
			<option value="realized">Realized</option>
			<option value="completed">Completed</option>
		</select>
		<br>
		<input type="submit" value="Upload" name="submit">
	</form>
</div>
</body>
