<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

include_once "includes/header.php";

// Include the helper PayWeb 3 class.
require_once 'classes/paygate.payweb3.php';

// Prepare PayGate PayWeb Data.
$data = array(
    'PAYGATE_ID'       => $paygate_id,
    'REFERENCE'        => filter_var( $_POST['REFERENCE'], FILTER_SANITIZE_STRING ),
    'AMOUNT'           => filter_var( $_POST['AMOUNT'] * 100, FILTER_SANITIZE_NUMBER_INT ),
    'CURRENCY'         => filter_var( $_POST['CURRENCY'], FILTER_SANITIZE_STRING ),
    'RETURN_URL'       => filter_var( $_POST['RETURN_URL'], FILTER_SANITIZE_URL ),
    'TRANSACTION_DATE' => filter_var( $_POST['TRANSACTION_DATE'], FILTER_SANITIZE_STRING ),
    'LOCALE'           => filter_var( $_POST['LOCALE'], FILTER_SANITIZE_STRING ),
    'COUNTRY'          => filter_var( $_POST['COUNTRY'], FILTER_SANITIZE_STRING ),
    'EMAIL'            => filter_var( $_POST['EMAIL'], FILTER_SANITIZE_EMAIL ),
);

(int) $logger > 0 ? $log->info( 'Data for submission to PayGate: ', $data ) : '';

// Set the session vars once we have cleaned the inputs.
$_SESSION['reference'] = $data['REFERENCE'];
$_SESSION['amount']    = $data['AMOUNT'];
$_SESSION['email']     = $data['EMAIL'];
$_SESSION['currency']  = $data['CURRENCY'];

// Initiate the PayWeb 3 helper class.
$PayWeb3         = new PayGate_PayWeb3();
$the_process_url = $PayWeb3::$process_url;

// Set the encryption key of your PayGate PayWeb3 configuration.
$PayWeb3->setEncryptionKey( $encryption_key );

// Set the array of fields to be posted to PayGate.
$PayWeb3->setInitiateRequest( $data );

// Do the curl post to PayGate.
$returnData = $PayWeb3->doInitiate();

if ( !$returnData ) {
    //There has been an error processing the request
    $html = <<<HTML
<div class="alert alert-danger">
<p>There has been an error processing the payment</p>
<p>The returned error message is: $PayWeb3->lastError</p>
<p>A message "PGID_NOT_EN" most likely means the currency selected is not allowed</p>
<a href="$url">Click here to try again</a>
</div>
HTML;
    echo $html;
}

$payment_form = '';

if ( isset( $PayWeb3->processRequest ) || isset( $PayWeb3->lastError ) ) {
    // We have received a response from PayWeb3.
    if ( !isset( $PayWeb3->lastError ) ) {
        // It is not an error, so continue.
        // Check that the checksum returned matches the checksum we generate.
        $isValid = $PayWeb3->validateChecksum( $PayWeb3->initiateResponse );

        if ( $isValid ) {
            // If the checksums match loop through the returned fields and create the redirect form.
            foreach ( $PayWeb3->processRequest as $key => $value ) {
                $payment_form .= <<<HTML
                    <input type="hidden" name="{$key}" value="{$value}" />
HTML;
            }
        }
    }
    // Submit form as/when needed.
    $logger == '2' ? $log->info( 'Submitting form to PayGate: ', $PayWeb3->processRequest ) : '';
    $payment_form .= <<<HTML
                    <input class="btn btn-success btn-block" id="check-sum" type="submit" name="btnSubmit" value="Redirect" />
HTML;
} else {
    $logger == '2' ? $log->error( 'Submitting form to PayGate: ', ['form' => $payment_form] ) : '';
}
echo <<<HTML
                <h2>Redirect Page</h2>
                <p>Redirecting you to PayGate...</p>
                <form action="$the_process_url" method="post" name="paygate_process_form" id="theForm">
                    $payment_form
                </form>

                <script>
                    (function() {
                        document.getElementById("theForm").submit();
                    })();
                </script>
HTML;
include_once "includes/footer.php";
