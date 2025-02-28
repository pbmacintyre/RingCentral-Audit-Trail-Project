<?php
/** Copyright (C) 2019-2025 Paladin Business Solutions */

require_once(__DIR__ . '/includes/ringcentral-php-functions.inc');
require_once(__DIR__ . '/includes/ringcentral-db-functions.inc');
require_once(__DIR__ . '/includes/ringcentral-functions.inc');
require_once(__DIR__ . '/includes/ringcentral-curl-functions.inc');

show_errors();

require(__DIR__ . '/includes/vendor/autoload.php');

Dotenv\Dotenv::createImmutable(__DIR__ . "/includes")->load();

$client_id = $_ENV['RC_APP_CLIENT_ID'];
$client_secret = $_ENV['RC_APP_CLIENT_SECRET'];

$destination_array = array();

$table = "clients";
$columns_data = array("*",);
$db_result = db_record_select($table, $columns_data);

foreach ($db_result as $key => $row) {
    $tokens = refresh_tokens($row['client_id'], $row['refresh']);

	// build destination array
	$destination_array[$key] = [
		"access" => $tokens['accessToken'],
		"extension" => $row['extension_id'],
		"from_number" => $row['from_number'],
		"to_number" => $row['to_number'],
		"tm_chat_id" => $row['team_chat_id'],
	];

	// check audit log for any pertinent events in the last 10 minutes
	$audit_data = get_audit_data($tokens['accessToken']);
	// echo_spaces("audit array of events", $audit_data);
}

foreach ($destination_array as $value) {
	if ($value['from_number'] !== "" && $value['to_number'] !== "") {
		// [3] send event data to admins via SMS
		send_admin_sms($destination_array, $audit_data);
	}
	if ($value['tm_chat_id'] !== "" ) {
		// [4] post the event to a TM group
		send_team_message($destination_array, $audit_data);
	}
}

//$message = "CRON runs every 30 minutes";
echo_spaces("CRON code finished running");
//send_basic_sms ($tokens['accessToken'], $message);
