<?php
// Connect to Database
$db = new mysqli('127.0.0.1', 'root', 'password', 'aiesec');

if ($db->connect_errno > 0)
	die('Unable to connect to database [' . $db->connect_error . ']');

define('ENV', "dev");
date_default_timezone_set("Asia/Kolkata");
