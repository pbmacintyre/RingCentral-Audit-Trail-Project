<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 */
ob_start();
session_start();

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-db-functions.inc');
require_once('includes/ringcentral-php-functions.inc');
require_once('includes/ringcentral-curl-functions.inc');

//show_errors();

page_header();
?>
    <script>
        function showHideSMS() {
            const checkbox = document.getElementById("SMSEnable");
            const rows = document.querySelectorAll('.SMSToggle');

            if (checkbox.checked) {
                rows.forEach(row => {
                    row.style.display = 'table-row';
                });
            } else {
                rows.forEach(row => {
                    row.style.display = 'none';
                });
            }
        }

        function showHideTM() {
            const checkbox = document.getElementById("TMEnable");
            const rows = document.querySelectorAll('.TMToggle');

            if (checkbox.checked) {
                rows.forEach(row => {
                    row.style.display = 'table-row';
                });
            } else {
                rows.forEach(row => {
                    row.style.display = 'none';
                });
            }
        }
    </script>


<?php
function show_form($message, $auth, $label = "", $print_again = false, $color = "#008EC2") {
	$accessToken = $_SESSION['access_token'];
	$accountId = $_SESSION['account_id'];
	$extensionId = $_SESSION['extension_id'];
	?>
    <form action="" method="post">
        <table class="CustomTable">
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <img src="images/rc-logo.png"/>
                    <h2><?php app_name(); ?></h2>
					<?php
					echo_plain_text($message, $color, "large");
					?>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <hr>
                </td>
            </tr>
			<?php if ($auth == 1) {
				// already in DB, already "authorized" as admin, edit existing data
				auth_level_1($accessToken, $accountId, $extensionId);
			} ?>
			<?php if ($auth == 2) {
				// admin level but newly created DB record, still needs to select how to get notifications
				auth_level_2($accessToken, $accountId, $extensionId);
			} ?>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <input type="submit" class="submit_button" value="   Save   " name="save">
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

function check_form($auth) {
	$print_again = false;
	$label = "";
	$message = "";

	$from_number = htmlspecialchars(strip_tags($_POST['from_number']));
	$to_number = htmlspecialchars(strip_tags($_POST['to_number']));
	$chat_id = htmlspecialchars(strip_tags($_POST['chat_id']));

	// check the formatting of the mobile # == +19991234567
	$pattern = '/^\+\d{11}$/'; // Assumes 11 digits after the '+'

	if ($from_number != "-1" && !preg_match($pattern, $to_number)) {
		$print_again = true;
		$label = "to_number";
		$message = "The mobile TO number is not in the correct format of +19991234567";
	}
	if ($from_number != "-1" && $to_number == "") {
		$print_again = true;
		$label = "to_number";
		$message = "Please provide a valid mobile number combination to receive admin messages.";
	}
	if ($from_number == "-1" && $chat_id == "-1") {
		$print_again = true;
		$label = "";
		$message = "Please provide either a phone number combination or a Group Chat to receive admin messages.";
	}
	// end edit checks
	if ($print_again == true) {
		$color = "red";
//        $message .= " From: " . $from_number . " chat id: " .  $chat_id;
		show_form($message, $auth, $label, $print_again, $color);
	} else {
		// update the record with validated information
		$accountId = $_SESSION['account_id'];
		$extensionId = $_SESSION['extension_id'];

		$table = "clients";
		$where_info = array("account", $accountId, "extension_id", $extensionId,);
		$condition = "AND";
		$fields_data = array(
			"from_number" => $from_number,
			"to_number" => $to_number,
			"team_chat_id" => $chat_id,
		);
		db_record_update($table, $fields_data, $where_info, $condition);

		// create admin webhook, there may already be an admin webhook so let the function test that
		ringcentral_create_admin_webhook_subscription($accountId, $_SESSION['access_token']);

		// if from & to number exist create sms webhook,
		if ($from_number && $to_number) {
			$sms_webhook_id = ringcentral_create_sms_webhook_subscription($accountId, $extensionId, $_SESSION['access_token']);
		} else {
			$sms_webhook_id = 0;
		}

		// store new webhook ids
		$fields_data = array(
			"sms_webhook" => $sms_webhook_id,
		);
		db_record_update($table, $fields_data, $where_info, $condition);

		header("Location: authorization_complete.php");

	}
}

/* ============= */
/*  --- MAIN --- */
/* ============= */
if (isset($_SESSION['form_token']) && $_GET['token'] == $_SESSION['form_token']) {
	$auth = $_GET['auth'];
	if (isset($_POST['save'])) {
		check_form($auth);
	} elseif ($auth == 1) {
		$message = "Your account has already been authorized please make any desired edits";
		show_form($message, $auth);
	} elseif ($auth == 2) {
		$message = "Your account will be authorized. <br/> Please provide the following additional information";
		show_form($message, $auth);
	} elseif ($auth == 0) {
		$message = "The provided account is not an admin level account.";
		show_form($message, $auth);
	}
} else {
    $_SESSION['form_token'] = "";
	header("Location: index.php");
}

ob_end_flush();
page_footer();
