<?php
// Connect to Database
$db = new mysqli('127.0.0.1', 'root', 'password', 'aiesec2');

if ($db->connect_errno > 0)
	die('Unable to connect to database [' . $db->connect_error . ']');

define('ENV', "dev");
date_default_timezone_set("Asia/Kolkata");
define('MANDRILL_API_KEY', "_jcQip61xB1p7TVgJBz2bw");