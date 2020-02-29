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

// Get current time.
$time = $_SERVER['REQUEST_TIME'];

// Insert the returned data as well as the merchant specific data PAYGATE_ID and REFERENCE in array.
$data = array(
    'PAYGATE_ID'         => $paygate_id,
    'PAY_REQUEST_ID'     => isset( $_POST['PAY_REQUEST_ID'] ) ? $_POST['PAY_REQUEST_ID'] : '',
    'TRANSACTION_STATUS' => isset( $_POST['TRANSACTION_STATUS'] ) ? $_POST['TRANSACTION_STATUS'] : '',
    'REFERENCE'          => isset( $_SESSION['reference'] ) ? $_SESSION['reference'] : '',
    'CHECKSUM'           => isset( $_POST['CHECKSUM'] ) ? $_POST['CHECKSUM'] : '',
);

// Initiate the PayWeb 3 helper class.
$PayWeb3 = new PayGate_PayWeb3();

// Set the encryption key of your PayGate PayWeb3 configuration.
$PayWeb3->setEncryptionKey( $encryption_key );

// Check that the checksum returned matches the checksum we generate.
$isValid = $PayWeb3->validateChecksum( $data );

//Do PayWeb query to get full set of results
if ( $isValid ) {
    $queryRequest                   = [];
    $queryRequest['PAYGATE_ID']     = $paygate_id;
    $queryRequest['PAY_REQUEST_ID'] = $data['PAY_REQUEST_ID'];
    $queryRequest['REFERENCE']      = $data['REFERENCE'];

    $PayWeb3->setQueryRequest( $queryRequest );
    $cnt     = 0;
    $isValid = false;
    while ( !$isValid && $cnt < 10 ) {
        if ( $PayWeb3->doQuery() ) {
            $queryResponse = $PayWeb3->queryResponse;
            $isValid       = $PayWeb3->validateChecksum( $queryResponse );
            if ( $isValid ) {
                $transaction_status = $queryResponse['TRANSACTION_STATUS'];
            }
        } else {
            $cnt++;
        }
    }
}

// Prepare body HTML.
$body_html       = '';
$create_new_html = "<p>To create a new transaction, <a href={$url}>click here.<a/></p>";
$try_again_html  = "<p>To try again, <a href={$url}?tryagain=true>click here.<a/></p>" . $create_new_html;

if ( $isValid ) {
    $body_html .= "<h2>Transaction {$PayWeb3->getTransactionStatusDescription( $transaction_status )}</h2>";
    if ( $transaction_status != 1 ) {
        (int) $logger > 0 ? $log->notice( 'Transaction not authorized', $queryResponse ) : '';
        $body_html .= $try_again_html;
    } else {
        (int) $logger > 0 ? $log->notice( 'Transaction authorized', $queryResponse ) : '';
        $body_html .= $create_new_html;
    }
} else {
    (int) $logger > 0 ? $log->warning( 'Checksum validation on return failed', ['received_data' => $data] ) : '';
    $body_html .= "<h2>Transaction Invalid</h2>";
    $body_html .= $try_again_html;
}
echo <<<EOT
            <div id="result-page">
                $body_html
            </div>
EOT;
include_once "includes/footer.php";
