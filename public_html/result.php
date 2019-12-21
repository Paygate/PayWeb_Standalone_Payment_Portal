<?php
/*
 * Copyright (c) 2019 PayGate (Pty) Ltd
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

// Prepare body HTML.
$body_html       = '';
$create_new_html = "<p>To create a new transaction, <a href={$url}>click here.<a/></p>";
$try_again_html  = "<p>To try again, <a href={$url}?tryagain=true>click here.<a/></p>" . $create_new_html;

if ( $isValid ) {
    $body_html .= "<h2>Transaction {$PayWeb3->getTransactionStatusDescription( $data['TRANSACTION_STATUS'] )}</h2>";
    if ( $data['TRANSACTION_STATUS'] != 1 ) {
        $body_html .= $try_again_html;
    } else {
        $body_html .= $create_new_html;
    }
} else {
    $body_html .= "<h2>Transaction Invalid</h2>";
    $body_html .= $try_again_html;
}
echo <<<EOT
            <div id="result-page">
                $body_html
            </div>
EOT;
include_once "includes/footer.php";
