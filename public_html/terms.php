<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

include_once "includes/header.php";

// This is the Terms and Conditions Page.
$isTerms = true;

$export_restriction = $export_restriction != '' ? '<li>Export restriction<br />' . $export_restriction . '.</li>' : '';

// Prepare body HTML.
echo <<<EOT
                <h2>Terms and Conditions</h2>
                <ol>
                    <li>Detailed description of goods and/or services<br />
                    $your_company is a $company_type in the $industry industry that $what_your_company_does.</li>
                    <li>Delivery policy<br />
                    Subject to availability and receipt of payment, requests will be processed within $processed_days and delivery confirmed by way of $method_of_confirmation.</li>
                    $export_restriction
                    <li>Return and Refunds policy<br />
                    $returns_and_refund_policy.</li>
                    <li>Customer Privacy policy<br />
                    $your_company shall take all reasonable steps to protect the personal information of users. For the purpose of this clause, "personal information" shall be defined as detailed in the Promotion of Access to Information Act 2 of 2000 (PAIA). The PAIA may be downloaded from: <a href="http://www.polity.org.za/attachment.php?aa_id=3569" target="_blank">http://www.polity.org.za/attachment.php?aa_id=3569</a>.</li>
                    <li>Payment options accepted<br />
                    Payment may be made via $payment_options_accepted.</li>
                    <li>Card acquiring and security<br />
                    Card transactions will be acquired for $your_company via PayGate (Pty) Ltd who are the approved payment gateway for all South African Acquiring Banks. PayGate uses the strictest form of encryption, namely Secure Socket Layer 3 (SSL3) and no Card details are stored on the website. Users may go to <a href="www.paygate.co.za" target="_blank">www.paygate.co.za</a> to view their security certificate and security policy.</li>
                    <li>Customer details separate from card details<br />
                    Customer details will be stored by $your_company separately from card details which are entered by the client on PayGate’s secure site. For more detail on PayGate refer to <a href="www.paygate.co.za" target="_blank">www.paygate.co.za</a>.</li>
                    <li>Merchant Outlet country and transaction currency<br />
                    The merchant outlet country at the time of presenting payment options to the cardholder is South Africa. Transaction currency is South African Rand (ZAR).</li>
                    <li>Responsibility<br />
                    $your_company takes responsibility for all aspects relating to the transaction including sale of goods and services sold on this website, customer service and support, dispute resolution and delivery of goods.</li>
                    <li>Country of domicile<br />
                    This website is governed by the laws of South Africa and $your_company chooses as its domicilium citandi et executandi for all purposes under this agreement, whether in respect of court process, notice, or other documents or communication of whatsoever nature: $domicilium_citandi_et_executandi.</li>
                    <li>Variation<br />
                    $your_company may, in its sole discretion, change this agreement or any part thereof at any time without notice.</li>
                    <li>Company information<br />
                    This website is run by $company_structure based in South Africa Trading trading as $trading_name and with registration number $registration_number and $directors_members_owners.</li>
                    <li>$your_company contact details<br />
                    Company Physical Address: $company_address<br />
                    Email: <a href="mailto:$company_email">$company_email</a><br />
                    Telephone: <a href="tel:$company_telephone">$company_telephone</a></li>
                </ol>
EOT;
include_once "includes/footer.php";
