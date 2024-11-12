<?php

ob_start();
session_start();

require_once('includes/ringcentral-db-functions.inc');
require_once('includes/ringcentral-php-functions.inc');
require_once('includes/ringcentral-curl-functions.inc');

show_errors();

require('includes/vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/includes")->load();

$client_id = $_ENV['RC_APP_CLIENT_ID'];
$client_secret = $_ENV['RC_APP_CLIENT_SECRET'];

if (isset($_GET['code'])) {

    $auth_code = htmlentities(strip_tags($_GET['code']));
    $redirect_uri = $_ENV['RC_REDIRECT_URL'];
    $endpoint = 'https://platform.ringcentral.com/restapi/oauth/token';

    $params = [
        'grant_type' => 'authorization_code',
        'code' => $auth_code,
        'redirect_uri' => $redirect_uri,
    ];

    $headers = [
        'Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret),
        'Content-Type: application/x-www-form-urlencoded'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    //echo_spaces("authorization data", $data);

    $accessToken    = $data['access_token'];
    $refreshToken   = $data['refresh_token'];
    $extensionId    = $data['owner_id'];

    /* ====== get user account information ==== */
    $account_url = "https://platform.ringcentral.com/restapi/v1.0/account/~";

    $account_ch = curl_init();

    // Set cURL options
    curl_setopt_array($account_ch, [
        CURLOPT_URL => $account_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            "Accept: application/json"
        ],
    ]);

    $account_response = curl_exec($account_ch);
    curl_close($account_ch);
    $account_data = json_decode($account_response, true);
//    echo_spaces("account data", $account_data);
    $accountId = $account_data['id'];

    // check if this account has already been authorized
    $table = "clients";
    $columns_data = array("client_id");
    $where_info = array ("account", $accountId, "extension_id", $extensionId, );
    $condition = "AND" ;
    $db_result = db_record_select($table, $columns_data, $where_info, $condition);

//    echo_spaces("DB result", $db_result);

    if (is_admin($accessToken, $accountId, $extensionId)) $isAdmin = true;

    if ($db_result || is_admin($accessToken, $accountId, $extensionId) == false) {
        $auth = 0 ;
    } else {
        // save the infomration to the DB
        $columns_data = array(
            "account" => $accountId,
            "extension_id" => $extensionId,
            "access" => $accessToken,
            "refresh" => $refreshToken,);
        db_record_insert($table, $columns_data);
        $_SESSION['account_id'] = $accountId;
        $_SESSION['extension_id'] = $extensionId;
        $_SESSION['access_token'] = $accessToken;
        $auth = 1 ;
    }
        header("Location: authorized.php?authorized=$auth");
}

ob_end_flush();
