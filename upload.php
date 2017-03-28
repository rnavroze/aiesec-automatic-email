<?php
// Some POST data
$entity_id = $_POST['entity'];
$status = $_POST['status'];

// We want to put the file into html/STATUS-ENTITY_ID.html
$target_dir = "html/";
$fname = "$status-$entity_id.html";

$target_file = $target_dir . $fname;

// Flag to check if upload was successful
$upload_ok = true;
$filetype = pathinfo($_FILES["html_file"]["name"], PATHINFO_EXTENSION);

if (isset($_POST["submit"]))
{
	if ($filetype != "html")
	{
		echo "File is not an HTML file.";
		$upload_ok = false;
	}
}

// Move uploaded file to where we want it to be
if ($upload_ok)
{
	if (move_uploaded_file($_FILES["html_file"]["tmp_name"], $target_file))
		header("Location: index.php?success=true");
	else
		echo "An error occurred while uploading the file.";
}