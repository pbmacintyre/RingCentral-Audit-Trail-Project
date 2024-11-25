<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 */
ob_start();
session_start();

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-db-functions.inc');
require_once('includes/ringcentral-php-functions.inc');

//show_errors();

page_header(1);  // set back to 1 when recaptchas are set in the .ENV file

function show_form($message, $label = "", $print_again = false) { ?>

    <form action="" method="post">
        <table class="CustomTable">
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <img src="images/rc-logo.png"/>
                    <h2><?php app_name(); ?></h2>
					<?php
					if ($print_again == true) {
//                        echo "<p class='msg_bad'>" . $message . "</strong></font>";
						echo_plain_text($message, "red", "large");
					} else {
//                        echo "<p class='msg_good'>" . $message . "</p>";
						echo_plain_text($message, "#008EC2", "large");
					} ?>
                </td>
            </tr>
            <tr class="CustomTable">
                <td class="CustomTableFullCol">
                    <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <input type="submit" class="submit_button" value="   Authorize / Login   " name="authorize">
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <hr>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
					<?php app_version(); ?>
                </td>
            </tr>
        </table>
    </form>
	<?php
}

/* ============= */
/*  --- MAIN --- */
/* ============= */
require(__DIR__ . '/includes/vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/includes')->load();

//echo_spaces("Dot path", $dotenv);
$client_id = $_ENV['RC_APP_CLIENT_ID'];
$redirect_url = $_ENV['RC_REDIRECT_URL'];

if (isset($_POST['authorize'])) {
	generate_form_token();
	$authorization_url = "https://platform.ringcentral.com/restapi/oauth/authorize?response_type=code&client_id={$client_id}&redirect_uri={$redirect_url}";
	header("Location: $authorization_url");
} elseif (isset($_GET['auth'])) {
    if ($_GET['auth'] == 'N') {
		$message = "The provided account is not Admin level. Please use another account or increase permissions on the provided account and then try again. <br/>";
		show_form($message, "", true);
	} elseif ($_GET['auth'] == 'X') {
		$message = "The account login process was cancelled.";
		show_form($message, "", true);
	} else {
		$message = "";
		show_form($message, "", true);
	}
} else {
	$message = "Please authorize your account or Login to make changes. <br/>";
	show_form($message);
}

ob_end_flush();
page_footer();
