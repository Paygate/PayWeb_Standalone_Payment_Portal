<?php
/** @noinspection PhpMissingStrictTypesDeclarationInspection */

/*
 * Copyright (c) 2024 Payfast (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

include_once 'includes/header.php';

// Include the helper class.
require_once 'classes/paygate.payweb3.php';
require_once 'classes/PayGateRequest.php';

// Initiate the helper class.
$payWeb3 = new PayGatePayWeb3();

$paygateRequest = new PayGateRequest();

$payWeb3->validateForm();

// Prepare Paygate Data.
$reference = filter_var($_POST['REFERENCE'], FILTER_SANITIZE_SPECIAL_CHARS);
$dataArray = [
    'PAYGATE_ID'       => $paygate_id,
    'REFERENCE'        => $reference,
    'AMOUNT'           => filter_var($_POST['AMOUNT'] * 100, FILTER_SANITIZE_NUMBER_INT),
    'CURRENCY'         => filter_var($_POST['CURRENCY'], FILTER_SANITIZE_SPECIAL_CHARS),
    'RETURN_URL'       => filter_var($_POST['RETURN_URL'], FILTER_SANITIZE_URL) . '?reference=' . $reference,
    'TRANSACTION_DATE' => filter_var($_POST['TRANSACTION_DATE'], FILTER_SANITIZE_SPECIAL_CHARS),
    'LOCALE'           => filter_var($_POST['LOCALE'], FILTER_SANITIZE_SPECIAL_CHARS),
    'COUNTRY'          => filter_var($_POST['COUNTRY'], FILTER_SANITIZE_SPECIAL_CHARS),
    'EMAIL'            => filter_var($_POST['EMAIL'], FILTER_SANITIZE_EMAIL),
];

if ((int)$logger > 0) {
    $logger->info('Data for submission to Paygate: ', $dataArray);
}

// Set the session vars once we have cleaned the inputs.
$_SESSION['reference'] = $dataArray['REFERENCE'];
$_SESSION['amount']    = $dataArray['AMOUNT'];
$_SESSION['email']     = $dataArray['EMAIL'];
$_SESSION['currency']  = $dataArray['CURRENCY'];


$the_process_url = PayGatePayWeb3::$processUrl;

// Set the encryption key of your Paygate configuration.
$payWeb3->setEncryptionKey($encryption_key);

// Set the array of fields to be posted to Paygate.
$paygateRequest->setInitRequest($dataArray);

// Do the curl post to Paygate.
$returnData = $paygateRequest->doInitiate();

if (!$returnData) {
    //There has been an error processing the request
    $html = <<<HTML
<div class="alert alert-danger">
<p>There has been an error processing the payment</p>
<p>The returned error message is: $paygateRequest->lastError</p>
<p>A message "PGID_NOT_EN" most likely means the currency selected is not allowed</p>
<a href="$url">Click here to try again</a>
</div>
HTML;
    echo $html;
}

$payment_form = '';

if (isset($paygateRequest->processRequest) || isset($paygateRequest->lastError)) {
    // We have received a response
    if (!isset($paygateRequest->lastError)) {
        // It is not an error, so continue.
        // Check that the checksum returned matches the checksum we generate.
        $isValid = $paygateRequest->validateChecksum($paygateRequest->initiateResponse);

        if ($isValid) {
            // If the checksums match loop through the returned fields and create the redirect form.
            foreach ($paygateRequest->processRequest as $key => $value) {
                $payment_form .= <<<HTML
                    <input type="hidden" name="$key" value="$value" />
HTML;
            }
        }
    }
    // Submit form as/when needed.
    if ($logger === '2') {
        $logger->info('Submitting form to Paygate: ', $paygateRequest->processRequest);
    }

    $payment_form .= <<<HTML
                    <input class="btn btn-success btn-block" id="check-sum" type="submit" name="btnSubmit" value="Redirect" />
HTML;
} elseif ($logger == '2') {
    $logger->error('Submitting form to Paygate: ', ['form' => $payment_form]);
}
echo <<<HTML
                <h2>Redirect Page</h2>
                <p>Redirecting you to Paygate...</p>
                <form action="$the_process_url" method="post" name="paygate_process_form" id="theForm">
                    $payment_form
                </form>

                <script>
                    (function() {
                        document.getElementById("theForm").submit();
                    })();
                </script>
HTML;
include_once 'includes/footer.php';
