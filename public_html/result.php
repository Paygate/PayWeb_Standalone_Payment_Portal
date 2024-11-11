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

// Get current time.
$time = $_SERVER['REQUEST_TIME'];

$reference = filter_var($_GET['reference'], FILTER_SANITIZE_SPECIAL_CHARS);

// Insert the returned data as well as the merchant specific data PAYGATE_ID and REFERENCE in array.
$dataArray = [
    'PAYGATE_ID'         => $paygate_id,
    'PAY_REQUEST_ID'     => $_POST['PAY_REQUEST_ID'] ?? '',
    'TRANSACTION_STATUS' => $_POST['TRANSACTION_STATUS'] ?? '',
    'REFERENCE'          => $reference ?? '',
    'CHECKSUM'           => $_POST['CHECKSUM'] ?? '',
];

// Initiate the helper class.
$payWeb3 = new PayGatePayWeb3();

$paygateRequest = new PayGateRequest();

// Set the encryption key of your Paygate configuration.
$payWeb3->setEncryptionKey($encryption_key);

// Check that the checksum returned matches the checksum we generate.
$isValid = $paygateRequest->validateChecksum($dataArray);

// Do query to get full set of results
if ($isValid) {
    $queryRequest                   = [];
    $queryRequest['PAYGATE_ID']     = $paygate_id;
    $queryRequest['PAY_REQUEST_ID'] = $dataArray['PAY_REQUEST_ID'];
    $queryRequest['REFERENCE']      = $dataArray['REFERENCE'];

    $paygateRequest->setQueryRequest($queryRequest);
    $count   = 0;
    $isValid = false;
    while (!$isValid && $count < 10) {
        if ($paygateRequest->doQuery()) {
            $queryResponse = $paygateRequest->queryResponse;
            $isValid       = $paygateRequest->validateChecksum($queryResponse);
            if ($isValid) {
                $transaction_status = $queryResponse['TRANSACTION_STATUS'];
            }
        } else {
            $count++;
        }
    }
}

// Prepare body HTML.
$body_html       = '';
$create_new_html = "<p>To create a new transaction, <a href=$url>click here.<a/></p>";
$try_again_html  = "<p>To try again, <a href=$url?tryagain=true>click here.<a/></p>" . $create_new_html;

if ($isValid) {
    $body_html .= "<h2>Transaction {$payWeb3->getTxnStatusDesc( $transaction_status )}</h2>";
    if ($transaction_status != 1) {
        if ((int)$logger > 0) {
            $logger->notice('Transaction not authorized', $queryResponse);
        }
        $body_html .= $try_again_html;
    } else {
        if ((int)$logger > 0) {
            $logger->notice('Transaction authorized', $queryResponse);
        }
        $body_html .= $create_new_html;
    }
} else {
    if ((int)$logger > 0) {
        $logger->warning('Checksum validation on return failed', ['received_data' => $dataArray]);
    }
    $body_html .= '<h2>Transaction Invalid</h2>';
    $body_html .= $try_again_html;
}
echo <<<EOT
            <div id="result-page">
                $body_html
            </div>
EOT;
include_once 'includes/footer.php';
