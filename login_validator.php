<?php
/**
 * Copyright (C) 2019-2024 Paladin Business Solutions
 *
 */

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-shopify-functions.inc');
require_once('includes/ringcentral-php-functions.inc');

ob_start();
session_start();

page_header();

function show_form ($message, $label = "", $print_again = false) { ?>

    <form action="" method="post">
        <input type="hidden" name="form_token" value="<?php echo generate_form_token(); ?>">
        <table class="CustomTable">
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <img src="images/rc-logo.png"/>
                    <h2> <?php app_name(); ?> </h2>
                    <?php
                    if ($print_again == true) {
                        echo "<p class='msg_bad'>" . $message . "</strong></font>";
                    } else {
                        echo "<p class='msg_good'>" . $message . "</p>";
                    } ?>
                    <hr>
                </td>
            </tr>
            <tr class="CustomTable">
                <td class="left_col">
                    <p style='display: inline; <?php if ($label == "six_digit_code") echo "color:red"; ?>'>SMS
                        Validation code:</p>
                </td>
                <td class="right_col"><input type="text" name="six_digit_code" <?php
                    if ($print_again) {
                        echo strip_tags($_POST['six_digit_code']);
                    }
                    ?>
                </td>
            </tr>
            <tr class="CustomTable">
                <td colspan="2" class="CustomTableFullCol">
                    <br/>
                    <input type="submit" class="submit_button" value="Submit">
                </td>
            </tr>
        </table>
    </form>
    <?php
}

function check_form () {

    $print_again = false;
    $label = "";
    $message = "";
    /* ======================================================================== */
    /* check the form token and then if good, process the rest of the form data */
    /* ======================================================================== */
    if (isset($_SESSION['form_token']) && $_POST['form_token'] == $_SESSION['form_token']) {

        $entered_code = strip_tags($_POST['six_digit_code']);
        $sesh_code = $_SESSION['six_digit_code'];

        /* =============================================================================== */

        if ($entered_code != $sesh_code) {
            $print_again = true;
            $label = "six_digit_code";
            $message = "The entered 6 digit code does not match the code sent to your mobile.";
        }

        if ($print_again == true) {
            show_form($message, $label, $print_again);
        } else {
            $_SESSION['login_good'] = 1;
            if ($_SESSION['login_action'] == true) {
                header("Location: homepage.php");
            } elseif ($_SESSION['delete_action'] == true) {
                header("Location: delete_account.php");
            }
        }
    } else {
        // if failed token testing then just go back to home page
        $_SESSION['form_token'] = "";
        header("Location: index.php");
    }
}

/* ============= */
/*  --- MAIN --- */
/* ============= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_form();
} else {
    $message = "Please provide the 6 digit code that was sent to the phone number <br/> we have on account ending in: " . $_SESSION['last_four'];
    show_form($message);
}

ob_end_flush();
?>