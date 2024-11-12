<?php

require_once('includes/ringcentral-db-functions.inc');

$table = "ringcentral_control";
$columns_data = array ("client_id", );
$where_info = array ("ringcentral_control_id", 1);
$db_result = db_record_select($table, $columns_data, $where_info );
$client_id = $db_result[0]['client_id'];

//$client_id = '24pu9Cwlu1fcAtmSh5osBv';
$authorization_url = "https://platform.ringcentral.com/restapi/oauth/authorize?response_type=code&client_id={$client_id}";

header("Location: $authorization_url");
exit();
