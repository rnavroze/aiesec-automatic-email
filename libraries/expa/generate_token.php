<?php
define('SESSION_FILE', "session.txt");
define('EXPA_AUTH_URL', "https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fexperience.aiesec.org%2Fsign_in&response_type=code&client_id=349321fd15814e9fdd2c5abe062a6fb10a27a95dd226fce287adb6c51d3de3df");
define('EXPA_LOGIN_URL', "https://auth.aiesec.org/users/sign_in");
define('TOKEN_FILE', "token.txt");
define('EXPA_TOKEN_VALIDITY_CHECK', "https://gis-api.aiesec.org/v2/current_person.json?access_token=");

// Include login details
include_once "apilogin.php";

// Check if the current token is valid
$access_token_x = file_get_contents(TOKEN_FILE);
$url = EXPA_TOKEN_VALIDITY_CHECK . $access_token_x;
$resp = @json_decode(file_get_contents($url));

// If there's no response or the response code is 401, token is invalid or there's an EXPA error
if ($resp == "" || @$resp->status->code == 401)
{
	// WARNING: curl is required for this script to run.
	$curl = curl_init();

	try
	{
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		else
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

		curl_setopt($curl, CURLOPT_COOKIEFILE, SESSION_FILE);
		curl_setopt($curl, CURLOPT_COOKIEJAR, SESSION_FILE);

		if (!file_exists(SESSION_FILE))
		{
			$fh = fopen(SESSION_FILE, "w");
			fclose($fh);
		}

		curl_setopt($curl, CURLOPT_URL, EXPA_AUTH_URL);
		$res = curl_exec($curl);


		preg_match('/Set-Cookie: expa_token=([A-Za-z0-9]+)/', $res, $matches);

		if (!isset($matches[1]))
		{
			preg_match('/name=\"authenticity_token\".*value="([A-Za-z0-9+=\/]+)"/', $res, $matches);

			if (!isset($matches[1]))
				throw new Exception("Could not get authenticity token.");

			$data = "user%5Bemail%5D=" . urlencode($un) . "&user%5Bpassword%5D=" . urlencode($pw) . '&authenticity_token=' . urlencode($matches[1]);

			curl_setopt($curl, CURLOPT_URL, EXPA_LOGIN_URL);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

			$res = curl_exec($curl);
			preg_match('/Set-Cookie: expa_token=([A-Za-z0-9]+)/', $res, $matches);

			if (!isset($matches[1]))
				throw new Exception("Could not get EXPA token.");
		}

		$return_token = $matches[1];
		file_put_contents(TOKEN_FILE, trim($matches[1]));
	}
	catch (Exception $e)
	{
		// Looks like EXPA is down!
		trigger_error("An irrecoverable error occurred. The details are as follows: " . $e->getMessage(), E_USER_ERROR);
	}
	finally
	{
		unlink(SESSION_FILE);
		curl_close($curl);
	}
}
else
	$return_token = $access_token_x;

$access_token = $return_token;
unset($return_token);