<?php
require_once __DIR__ . "/../unirest/Unirest.php";

class EXPA
{
	// String holding the access token
	private $accessToken = "";

	// API URL without trailing slash
	private $APIUrl = "https://gis-api.aiesec.org";

	// API version string
	private $APIVersion = "v2";

	// Default headers for Unirest
	private $defaultHeaders = [ 'Accept' => 'application/json' ];

	// Are we logged in as a User account?
	private $isUserAccount = false;

	// Are we debugging?
	private $debugMode = false;

	// Do we terminate on API down?
	private $terminateOnAPIDown = true;

	// Do we retry on 404?
	private $retryOn404 = false;

	// How many attempts before we shut down?
	private $retryAttempts = 0;
	private $retryAttemptsMax = 10;
	private $retryDelay = 60;

	// Initialize the API
	// If $un and $pw are omitted, it logs in with your API account
	// Returns: true if API key was successfully generated, false otherwise
	function __construct($un = null, $pw = null)
	{
	    Unirest\Request::verifyPeer(false); // Disables SSL cert validation FIXME
	    Unirest\Request::rebuildUrl(false); // EXPA API workaround
	    Unirest\Request::jsonOpts(true);    // Return associative arrrays
	    
	    if ($un === null && $pw === null)
	    	$this->accessToken = $this->getAccessToken();
	    else
	    {
	    	$this->accessToken = $this->getAccessToken($un, $pw);
			$this->isUserAccount = true;
		}

		return empty($this->accessToken);
	}

	// Generates an access token and returns it
	private function getAccessToken()
	{
		require "generate_token.php";
		return $access_token;
	}

	// Generate a new access token. Does not work if isUserAccount is true
	// Returns true if access token was regenerated, false otherwise
	// Does not forcefully generate a new access token, it checks if the current one has expired
	public function newAccessToken()
	{
		if ($this->isUserAccount)
			return false;

		if (!$this->checkAccessToken())
			$this->accessToken = $this->getAccessToken();
		else
			return false;

		return true;
	}

	// Returns whether the stored access token is valid or not
	private function checkAccessToken()
	{
		$check = $this->call("current_person", [], Unirest\Method::GET, false);
		return !isset($check['error_code']);
	}

	// Change the API URL without trailing slash
	public function setAPIUrl($url)
	{
		$this->APIUrl = $url;
	}

	// Change the API version
	public function setAPIVersion($version)
	{
		$this->APIVersion = $version;
	}

	// Enable debug mode
	public function debugMode($mode)
	{
		$this->debugMode = $mode;
		Unirest\Request::$debugMode = $mode;
	}

	// Enable terminate on API down
	public function terminateOnAPIDown($mode)
	{
		$this->terminateOnAPIDown = $mode;
	}

	// Toggle retryOn404
	public function retryOn404($mode)
	{
		$this->retryOn404 = $mode;
	}

	// Run an API call
	// Returns the body of the result if successful, or an array containing the error_code if not depending on the last argument
	public function call($apifunc, $arguments = [], $method = Unirest\Method::GET, $terminate_on_apidown = true)
	{
		$headers = $this->defaultHeaders;

		if ($terminate_on_apidown == false)
			$force_terminate_on_apidown = false;
		else
			$force_terminate_on_apidown = $this->terminateOnAPIDown;

		$apiurl = $this->APIUrl . '/' . $this->APIVersion . '/' . $apifunc . '.json';

		if (is_array($arguments))
			$arguments['access_token'] = $this->accessToken;
		else
		{
			$headers['Content-Type'] = 'application/json';
			$apiurl .= "?access_token=" . $this->accessToken;
		}

		try
		{
			$result = Unirest\Request::send($method, $apiurl, $arguments, $headers);
			if (($result->code == 404 && $this->retryOn404) || $result->code == 503)
			{
				if ($this->retryAttempts < $this->retryAttemptsMax)
				{
					$this->retryAttempts++;

					if ($this->debugMode)
						echo "EXPA returned " . $result->code . ". Waiting " . $this->retryDelay . "s.\n";

					sleep($this->retryDelay);

					if ($this->debugMode)
					 echo "Retrying (" . $this->retryAttempts . " of " . $this->retryAttemptsMax . ")\n";

					return $this->call($apifunc, $arguments, $method, $terminate_on_apidown);
				}
				else
				{
					if ($this->debugMode)
						echo "Call to $method failed.\n";

					if ($force_terminate_on_apidown)
						throw new Unirest\Exception("$apiurl => " . $result->code . " | " .  $result->raw_body);
					else
						return [ "error_code" => $result->code, "raw_body" => $result->raw_body ];
				}
			}
		}
		catch (Unirest\Exception $e)
		{
			$this->error(strval($e));
		}

		$this->retryAttempts = 0;
		return $result->body;
	}

	// Run a paginated API call. Make sure the time limit is set.
	// Returns only the [data] of the call, merged.
	public function call_paginated($apifunc, $arguments = [], $method = Unirest\Method::GET, $terminate_on_apidown = true)
	{
		global $db; // FIXME: fix horrible workaround

		$result = $this->call($apifunc, $arguments, $method, $terminate_on_apidown);
		if (isset($result['error_code']))
			return $result;
		else
		{
			if (!isset($result['paging']))
				return $result;
			else
				$pages = $result['paging']['total_pages'];
		}

		if ($this->debugMode)
			echo "Page 1 of $pages // ";

		$result = $result['data'];
		for ($page = 2; $page <= $pages; $page++)
		{
			$arg_with_pages = $arguments;
			$arg_with_pages['page'] = $page;
			$page_result = $this->call($apifunc, $arg_with_pages, $method, $terminate_on_apidown);

			if (isset($page_result['error_code']))
				return $page_result;

			$result = array_merge($result, $page_result['data']);
			if ($this->debugMode)
				echo "Page $page of $pages // ";
		}

		return $result;
	}

	// Derped with the naming syntax. Left old function for compatibility's sake
	public function callPaginated($apifunc, $arguments = [], $method = Unirest\Method::GET, $terminate_on_apidown = true)
	{
		return $this->call_paginated($apifunc, $arguments, $method, $terminate_on_apidown);
	}

	// Error handler
	private function error($text)
	{
		$errorcode = substr(md5(microtime()), 0, 6);
		error_log("[$errorcode] $text");
		die("An irrecoverable error has occurred. Kindly contact the developers with the error code: $errorcode");
		// TODO: more graceful
	}
}