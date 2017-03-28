# aiesec-automatic-email
**Setup**
1. Create libraries/init.php with the following code:
````
<?php
$db = new mysqli('<SQL SERVER>', '<SQL USERNAME>', '<SQL PASSWORD>', '<SQL DATABASE>');
  
if ($db->connect_errno > 0)
  	die('Unable to connect to database [' . $db->connect_error . ']');
  
define('ENV', "<dev> if development, <prod> if production");
date_default_timezone_set("Asia/Kolkata"); // Change this 
define('MANDRILL_API_KEY', "<MANDRILL API KEY>");
````
  
2. Import tables.sql.
3. Create a file in libraries/expa/apilogin.php with the following code:
````
<?php
$un = "EXPA_LOGIN_USERNAME";
$pw = "EXPA_LOGIN_PASSWORD";
````

4. Create a blank file libraries/expa/token.txt.
5. Create a blank folder called html/ in root for HTML file uploads. Make sure this file is writeable by PHP.
6. Put cron_sendemail.php on a CRON job.