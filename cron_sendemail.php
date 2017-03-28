<?php
// Import required libraries
require_once "libraries/init.php";
require_once "libraries/library.inc.php";
require_once "libraries/expa/expa.php";
require_once "libraries/mandrill/Mandrill.php";

// Prevent the script from hanging up
set_time_limit(0);
error_reporting(E_ALL);

// Set up our EXPA object & EP table
$expa = new EXPA();
$epdata_table = 'email_eplist';
$request_delay = defined('ENV') && ENV == 'dev' ? 0 : 3;
$emails_dir = "html/";

// Are we on dev environment?
$expa->debugMode(defined('ENV') && ENV == 'dev');

// Now, we want to get a list of all global EPs, but only who were interacted with in the last 2 days
// This is under the assumption that we run the script, at most, once a day
$eplist = $expa->call("people", [
	'filters[last_interaction][from]' => date('Y-m-d', strtotime("yesterday")),
	'filters[last_interaction][to]'   => date('Y-m-d')
]);
$eplist = $eplist['data'];

// Process EP list
foreach ($eplist as $ep)
{
	// First, check if the EP doesn't already exist on the table.
	$exists = fetch_one(run_query("SELECT id FROM $epdata_table WHERE epid = " . $db->escape_string($ep['id']) . " AND status = '" . $db->escape_string($ep['status']) . "'"));

	if (isset($exists['id']))
	{
		// Already there, no need to send any email
		echo "{$ep['id']} already exists with status {$ep['status']}, skipping.\n";
		continue;
	}

	// The API does not tell us the home MC for the EP. We could work around this by checking things like the phone no.
	// code or the EP manager's home MC, however, it is unreliable. For now, we can call another query on people/ID,
	// but the long term solution would be to update the API so that people.json returns the home MC too.
	$epdata = $expa->call("people/{$ep['id']}");
	$entity_id = $epdata['home_lc']['parent_id'];
	$status = $epdata['status'];

	// Put the data into the table
	insert_query([
		'epid'       => $epdata['id'],
		'entity_id'  => $entity_id,
		'status'     => $status,
		'email_sent' => 0
	], $epdata_table);

	// Send email
	// First, does this entity have an email to send?
	if (!file_exists($emails_dir . "$status-$entity_id.html"))
		echo "The file $entity_id-$status doesn't exist.\n";
	else
	{
		// For the sake of debugging, we will only send this email to the developer and not the intended recepient.
		$html = "<b>The intended recepient of this email is: {$ep['email']}.</b>";
		$to_email = "raihan@aiesec.in";

		// The subject of the email should depend upon the status
		// TODO: This is only an example. More cases can be added.
		switch ($status)
		{
			case "applied":
				$subject = "You've applied to an opportunity!";
			break;

			default:
				$subject = "Hello from AIESEC!";
			break;
		}

		// Okay, now we need to send this file
		$html .= file_get_contents($emails_dir . "$status-$entity_id.html");

		// We need to replace %EPNAME% with the EP's name
		$html = str_replace("%EPNAME%", $ep['full_name'], $html);

		// Run Mandrill
		$query = [
			'html'       => $html,
			'subject'    => $subject,
			'from_email' => "contact@myaiesec.in",
			'from_name'  => "AIESEC",
			'to'         => [
				['email' => $to_email]
			],
			'auto_text'  => true
		];

		try
		{
			$mandrill = new Mandrill(MANDRILL_API_KEY);
			$response = $mandrill->messages->send($query);

			if (isset($response['status']) && $response['status'] == 'error')
				echo "There was an error sending the email: " . $response['message'] . "\n";
			else
			{
				// Show success
				echo "Successfully sent to {$ep['id']}: $status-$entity_id.\n";
				// If successful, update the entry
				update_query(['email_sent' => 1], $epdata_table, "epid = " . $db->escape_string($ep['id']));
			}
			// TODO: if email was rejected, handle that also.
		}
		catch (Mandrill_Error $e)
		{
			echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
			throw $e;
		}
	}

	// Wait a few seconds so we don't stress the servers
	sleep($request_delay);
}

