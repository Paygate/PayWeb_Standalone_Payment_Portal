<?php
/*
 * Copyright (c) 2019 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * URI: https://github.com/PayGate/PayWeb_Standalone_Payment_Portal
 * Version: 1.0.0
 *
 * Released under the GNU General Public License
 */
require_once 'classes/paygate_currencies.php';

$images        = [];
$logos         = '';
$protocol      = isset( $_SERVER['HTTPS'] ) && ( $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off" ) ? "https" : "http";
$serverBaseUrl = rtrim( $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], "/" );

$assets = scandir( 'assets' );
foreach ( $assets as $asset ) {
    if ( strlen( $asset ) > 2 && substr( $asset, 0, 1 ) != "." ) {
        $name  = strstr( $asset, '.', true );
        $name  = str_replace( '-', '_', $name );
        $label = ucwords( str_replace( ['-', '_'], ' ', $name ) );
        $id    = strtolower( $name );
        array_push( $images, $name );
        $logos .= <<<LOGO
  <div class="form-check">
    <input type="checkbox" class="form-check-input" id="$id" name="$name" aria-describedby="{$name}_check"  >
    <label class="form-check-label" for="{$name}_check">{$label}<span><img src="assets/{$asset}" alt="{$label}"></span></label>
  </div>
LOGO;
    }
}
$logos .= <<<SAVE
  <div>
    <br/>
    <button type="submit" class="btn btn-primary">Save</button>
  </div>
</form>
</br>
SAVE;

//Populate dropdown for currencies
$currencies       = PayGate_Currencies::getCurrencies();
$currency_options = '<option value="0">-- Select currencies --</option>';
foreach ( $currencies as $currency ) {
    $currency_options .= '<option value="' . $currency['CurrencyCode'] . '">' . $currency['CurrencyCode'] . ' - ' . $currency['Currency'] . '</option>';
}
$permitted_currencies = "ZAR"; //Default currency if none selected

if ( isset( $_POST ) && count( $_POST ) > 0 ) {
    $post = $_POST;
    $fh   = fopen( 'includes/_env.php', 'w' );
    foreach ( $post as $item => $value ) {
        $item    = str_replace( '-', '_', $item );
        $item    = filter_var( $item, FILTER_SANITIZE_STRING );
        ${$item} = trim( ${$item} );

        if ( $item === 'company_email' ) {
            ${$item} = filter_var( $value, FILTER_SANITIZE_EMAIL );
        } elseif ( $item === 'baseUrl' ) {
            ${$item} = filter_var( $value, FILTER_SANITIZE_URL );
        } elseif ( in_array( $item, $images ) ) {
            if ( $value == 'on' ) {
                ${$item} = true;
            } else {
                ${$item} = false;
            }
        } elseif ( is_array( $value ) ) {
            $s = '';
            foreach ( $value as $v ) {
                $s .= $v . ',';
            }
            $s       = rtrim( $s, ',' );
            ${$item} = $s;
        } else {
            ${$item} = filter_var( $value, FILTER_SANITIZE_STRING );
        }
    }

    $content = <<<'CONTENT'
<?php
/*
 * Copyright (c) 2019 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

// Global Settings.
CONTENT;

    $content .= PHP_EOL;
    $content .= '$page_title                              = "' . $pageTitle . '"; // The Page Title.' . PHP_EOL;
    $content .= '$url                                     = "' . $baseUrl . '"; // The Base URL.' . PHP_EOL;
    $content .= '$encryption_key                          = "' . $encryption_key . '"; // Your encryption key.' . PHP_EOL;
    $content .= '$paygate_id                              = "' . $paygate_id . '"; // Your PayGate ID.' . PHP_EOL;
    $content .= '$expiry_date                             = "' . $expiry_date . '"; // The date the portal should become disabled (if needed).' . PHP_EOL;
    $content .= '$logger                                  = "' . $logger . '"; // Set to enable logging (0=none, 1=payments(default), 2=all)' . PHP_EOL;
    $content .= PHP_EOL . '// Terms and Conditions.' . PHP_EOL;
    $content .= '$your_company                            = "' . $your_company . '"; // Your company/organization name, e.g. ACME Clothes.' . PHP_EOL;
    $content .= '$company_type                            = "' . $company_type . '"; // Your company/organization type, e.g. business.' . PHP_EOL;
    $content .= '$industry                                = "' . $industry . '"; // Your industry, e.g. textiles.' . PHP_EOL;
    $content .= '$what_your_company_does                  = "' . $what_your_company_does . '"; // What your company does, e.g. sells clothes to retail.' . PHP_EOL;
    $content .= '$processed_days                          = "' . $processed_days . '"; // Days to Process, e.g. 10 days.' . PHP_EOL;
    $content .= '$method_of_confirmation                  = "' . $method_of_confirmation . '"; // Method of Confirmation, e.g. booking number / booking voucher etc. and must mention the use of courier and/or postal services and associated costs, if applicable.' . PHP_EOL;
    $content .= '$export_restriction                      = "' . $export_restriction . '"; // Optional, leave blank if not needed.' . PHP_EOL;
    $content .= '$payment_options_accepted                = "' . $payment_options_accepted . '"; // Payment options accepted, e.g. Visa and MasterCard.' . PHP_EOL;
    $content .= '$administration_fee                      = "' . $administration_fee . '"; // Administration Fee, e.g. 5%.' . PHP_EOL;
    $content .= '$returns_and_refund_policy               = "' . $returns_and_refund_policy . '"; // If appropriate – provide details of your policy regarding damaged goods. Also mention guarantees, warranties, etc.' . PHP_EOL;
    $content .= '$domicilium_citandi_et_executandi        = "' . $domicilium_citandi_et_executandi . '"; // Your domicilium citandi et executandi., e.g. 1 Example street, Pretoria, 0184.' . PHP_EOL;
    $content .= '$company_structure                       = "' . $company_structure . '"; // Company Structure, e.g. sole trader / private company / close corporation.' . PHP_EOL;
    $content .= '$trading_name                            = "' . $trading_name . '"; // Trading Name, e.g. ACME Trading.' . PHP_EOL;
    $content .= '$registration_number                     = "' . $registration_number . '"; // Registration Number, e.g. 2019/123456/01.' . PHP_EOL;
    $content .= '$directors_members_owners                = "' . $directors_members_owners . '"; // Directors/Members/Owners, e.g. 1 Director. ' . PHP_EOL;
    $content .= '$company_address                         = "' . $company_address . '"; // Company Physical Address, e.g. 1 Example street, Pretoria, 0184.' . PHP_EOL;
    $content .= '$company_email                           = "' . $company_email . '"; // Company Email, e.g. email@domain.com.' . PHP_EOL;
    $content .= '$company_telephone                       = "' . $company_telephone . '"; // Company Telephone, e.g. +2712-345-6789' . PHP_EOL;
    $content .= '$permitted_currencies                    = "' . $permitted_currencies . '"; // Permitted currencies, e.g. ZAR, USD' . PHP_EOL;
    $content .= PHP_EOL . '// Logo Images: set \'true\' to display or \'false\' to hide.' . PHP_EOL;
    $content .= '$Amex                                    = ' . ( isset( $Amex ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$DPO_SA                                  = ' . ( isset( $DPO_SA ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$Mastercard_Securecode                   = ' . ( isset( $Mastercard_Securecode ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$verified_by_visa                        = ' . ( isset( $verified_by_visa ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$Diners_Club                             = ' . ( isset( $Diners_Club ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$mastercard                              = ' . ( isset( $mastercard ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$SCode                                   = ' . ( isset( $SCode ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$masterpass                              = ' . ( isset( $masterpass ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$SiD_Secure_EFT                          = ' . ( isset( $SiD_Secure_EFT ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$PayGate_Risk_Engine_Logo_PayProtector   = ' . ( isset( $PayGate_Risk_Engine_Logo_PayProtector ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$PayGate_Certified_Developer_Seal        = ' . ( isset( $PayGate_Certified_Developer_Seal ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$PayGate_PayPartner_Logo                 = ' . ( isset( $PayGate_PayPartner_Logo ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$Visa                                    = ' . ( isset( $Visa ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$Visa_Checkout                           = ' . ( isset( $Visa_Checkout ) ? 'true' : 'false' ) . ';' . PHP_EOL;
    $content .= '$PayPal                                  = ' . ( isset( $PayPal ) ? 'true' : 'false' ) . ';' . PHP_EOL;

    fwrite( $fh, $content );
    fclose( $fh );
    unlink( 'index.php' );

    $fh = fopen( 'index.php', 'w' );
    fwrite( $fh, prepare_final_install_page() );
    fclose( $fh );
    header( 'Location: ' . $baseUrl );
}

echo <<<EOT
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Install Page</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <style>
        .container {
            margin-top: 15px;
        }
        img {
            margin-left: 15px;
            height: 20px;
        }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Standalone Portal</h1>
            <h2>Installation Wizard</h2>
EOT;

echo <<<FORM
<form action="index.php" method="post">
    <h3>Global Settings</h3>
    <p>Please note that full-stops (.) should not be added to the end of the below fields, as these will be added automatically.
  <div class="form-group">
    <label for="pageTitle">Page Title</label>
    <input type="text" class="form-control" id="pageTitle" name="pageTitle" aria-describedby="pageTitle" placeholder="Enter your page title" required>
    <small id="pageTitleHelp" class="form-text text-muted">This is the title your page will display, e.g. Standalone Portal.</small>
  </div>
  <div class="form-group">
    <label for="baseUrl">Base Url</label>
    <input type="text" class="form-control" id="baseUrl" name="baseUrl" aria-describedby="baseUrl" placeholder="Enter your site base url" required>
    <small id="baseUrlHelp" class="form-text text-muted">This is the base url for your site, e.g. $serverBaseUrl (don't add a / at the end of your url).</small>
  </div>
  <div class="form-group">
    <label for="paygate_id">PayGate ID</label>
    <input type="text" class="form-control" id="paygate_id" name="paygate_id" aria-describedby="paygate_id" placeholder="Enter your PayGate ID" required>
    <small id="paygate_idHelp" class="form-text text-muted">This is your PayGate ID, e.g. 10011072130.</small>
  </div>
  <div class="form-group">
    <label for="encryption_key">Encryption Key</label>
    <input type="text" class="form-control" id="encryption_key" name="encryption_key" aria-describedby="encryption_key" placeholder="Enter your encryption key" required>
    <small id="encryption_keyHelp" class="form-text text-muted">This is your encryption key, e.g. secret.</small>
  </div>
  <div class="form-group">
    <label for="expiry_date">Expiry Date</label>
    <input type="date" class="form-control" id="expiry_date" name="expiry_date" aria-describedby="expiry_date" placeholder="Enter your site's expiry date" value="2050-12-31"required>
    <small id="expiry_dateHelp" class="form-text text-muted">This is the date the portal should be disabled (if needed in the case of running a promotion or event), e.g. 2050-12-31.</small>
  </div>
  <div class="form-group">
    <label for="logger">Logging</label>
    <select class="form-control" id="logger" name="logger" aria-describedby="logger" >
    <option value="0" selected>No logging</option>
    <option value="1">Log all payment returns</option>
    <option value="2">Log all significant events</option>
    </select>
    <small id="loggerHelp" class="form-text text-muted">Set logging level (0=none(default), 1=payments, 2=all). Designed for development, leave this disabled on a live server.</small>
  </div>
  <hr>
  <h3>Terms and Conditions</h3>
  <div class="form-group">
    <label for="your_company">Company Name</label>
    <input type="text" class="form-control" id="your_company" name="your_company" aria-describedby="your_company" placeholder="Enter your company name" required>
    <small id="your_companyHelp" class="form-text text-muted">The name of your company or organization, e.g. Super Clothing Company (Pty) Ltd.</small>
  </div>
  <div class="form-group">
    <label for="company_type">Company Type</label>
    <input type="text" class="form-control" id="company_type" name="company_type" aria-describedby="company_type" placeholder="Enter your company type" required>
    <small id="company_typeHelp" class="form-text text-muted">This is your company's type, e.g. clothing manufacturer.</small>
  </div>
  <div class="form-group">
    <label for="industry">Industry</label>
    <input type="text" class="form-control" id="industry" name="industry" aria-describedby="industry" placeholder="Enter your industry" required>
    <small id="industryHelp" class="form-text text-muted">This is your industry, e.g. textiles.</small>
  </div>
  <div class="form-group">
    <label for="what_your_company_does">What you do</label>
    <input type="text" class="form-control" id="what_your_company_does" name="what_your_company_does" aria-describedby="what_your_company_does" placeholder="Enter your what your company does" required>
    <small id="what_your_company_doesHelp" class="form-text text-muted">What your company does, e.g. provide clothes for retail stores.</small>
  </div>
  <div class="form-group">
    <label for="processed_days">Processing Days</label>
    <input type="text" class="form-control" id="processed_days" name="processed_days" aria-describedby="processed_days" placeholder="Enter your days to process" required>
    <small id="processed_daysHelp" class="form-text text-muted">Days to process an order, e.g. 10 days.</small>
  </div>
  <div class="form-group">
    <label for="method_of_confirmation">Confirmation Method</label>
    <textarea class="form-control" id="method_of_confirmation" name="method_of_confirmation" aria-describedby="method_of_confirmation" placeholder="Enter your method of confirmation" required></textarea>
    <small id="method_of_confirmationHelp" class="form-text text-muted">Method of Confirmation, e.g. booking number / booking voucher etc. and must mention the use of courier and/or postal services and associated costs, if applicable.</small>
  </div>
  <div class="form-group">
    <label for="export_restriction">Export Restrictions</label>
    <input type="text" class="form-control" id="export_restriction" name="export_restriction" aria-describedby="export_restriction" value="The offering on this website is available to South African clients only" placeholder="Enter your export restrictions (Optional, leave blank if not needed)">
    <small id="export_restrictionHelp" class="form-text text-muted">Optional, leave blank if not needed.</small>
  </div>
  <div class="form-group">
    <label for="export_restriction">Payment options accepted</label>
    <input type="text" class="form-control" id="payment_options_accepted" name="payment_options_accepted" aria-describedby="payment_options_accepted" placeholder="Enter your payment options accepted">
    <small id="payment_options_accepted" class="form-text text-muted">Payment options accepted, e.g. Visa and MasterCard.</small>
  </div>
  <div class="form-group">
    <label for="administration_fee">Administration Fee</label>
    <input type="text" class="form-control" id="administration_fee" name="administration_fee" aria-describedby="administration_fee" placeholder="Enter your administration fee" required>
    <small id="administration_feeHelp" class="form-text text-muted">Administration fee, e.g. 5% or R10.</small>
  </div>
  <div class="form-group">
    <label for="returns_and_refund_policy">Returns and Refunds</label>
    <textarea class="form-control" id="returns_and_refund_policy" name="returns_and_refund_policy" aria-describedby="returns_and_refund_policy" placeholder="Enter your returns and refund policy" required>The provision of goods and services by us is subject to availability. In cases of unavailability, we will refund the client in full within 30 days. Cancellation of orders by the client will attract an administration fee</textarea>
    <small id="returns_and_refund_policyHelp" class="form-text text-muted">If appropriate – provide details of your policy regarding damaged goods. Also mention guarantees, warranties, etc.</small>
  </div>
  <div class="form-group">
    <label for="domicilium_citandi_et_executandi">Domicilium</label>
    <input type="text" class="form-control" id="domicilium_citandi_et_executandi" name="domicilium_citandi_et_executandi" aria-describedby="domicilium_citandi_et_executandi" placeholder="Enter your domicilium citandi et executandi" required>
    <small id="domicilium_citandi_et_executandiHelp" class="form-text text-muted">Your domicilium citandi et executandi, e.g. 1 Example Street, Pretoria, 0184.</small>
  </div>
  <div class="form-group">
    <label for="company_structure">Company Structure</label>
    <input type="text" class="form-control" id="company_structure" name="company_structure" aria-describedby="company_structure" placeholder="Enter your company structure" required>
    <small id="company_structureHelp" class="form-text text-muted">Company Structure, e.g. sole trader / private company / close corporation.</small>
  </div>
  <div class="form-group">
    <label for="trading_name">Trading Name</label>
    <input type="text" class="form-control" id="trading_name" name="trading_name" aria-describedby="trading_name"  placeholder="Enter your trading name" required>
    <small id="trading_nameHelp" class="form-text text-muted">Trading name, e.g. Super Clothing.</small>
  </div>
  <div class="form-group">
    <label for="registration_number">Registration Number</label>
    <input type="text" class="form-control" id="registration_number" name="registration_number" aria-describedby="registration_number" placeholder="Enter your registration number" required>
    <small id="registration_numberHelp" class="form-text text-muted">Registration Number, e.g. 2019/123456/45.</small>
  </div>
  <div class="form-group">
    <label for="directors_members_owners">Directors, Members and Owners</label>
    <input type="text" class="form-control" id="directors_members_owners" name="directors_members_owners" aria-describedby="directors_members_owners" placeholder="Enter your Directors/Members/Owners" required>
    <small id="directors_members_ownersHelp" class="form-text text-muted">Directors/Members/Owners, e.g. First Person / Second Person (Directors).</small>
  </div>
  <div class="form-group">
    <label for="company_address">Physical Address</label>
    <input type="text" class="form-control" id="company_address" name="company_address" aria-describedby="company_address"  placeholder="1 Example Street, Pretoria, 0184" required>
    <small id="company_addressHelp" class="form-text text-muted">Physical address of business, e.g. 1 Example Street, Pretoria, 0184.</small>
  </div>
  <div class="form-group">
    <label for="company_email">Company Email</label>
    <input type="email" class="form-control" id="company_email" name="company_email" aria-describedby="company_email"  placeholder="email@mydomain.com" required>
    <small id="company_emailHelp" class="form-text text-muted">Email of business, e.g. email@mydomain.com.</small>
  </div>
  <div class="form-group">
    <label for="company_telephone">Company Telephone</label>
    <input type="tel" class="form-control" id="company_telephone" name="company_telephone" aria-describedby="company_telephone" placeholder="+27 12 123 4567" required>
    <small id="company_telephoneHelp" class="form-text text-muted">Telephone of business, e.g. +27 12 123 4567.</small>
  </div>
  <hr>
  <h3>Currencies and Countries</h3>
  <div class="form-group">
    <label for="permitted_currencies">Permitted currencies</label>
    <select class="form-control" id="permitted_currencies" name="permitted_currencies[]" aria-describedby="permitted_currencies" multiple>
    $currency_options
    </select>
    <small id="permitted_currenciesHelp" class="form-text text-muted">Select currencies that can be used. Will default to ZAR if no selection is made</small>
  </div>
  <h3>Logo Images</h3>
FORM;

echo $logos;

echo <<<FOOT
</div>
</body>
</html>
FOOT;

function prepare_final_install_page()
{
    return <<<PAGE
<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd]
 * URI: https://github.com/PayGate/PayWeb_Standalone_Payment_Portal
 * Version: 1.0.0
 *
 * Released under the GNU General Public License
 */

include_once "includes/header.php";
include_once "classes/paygate_currencies.php";

/**
 * Checks for return from PayGate and handles it
 */
if (isset(\$_POST) && isset(\$_POST['TRANSACTION_STATUS'])) {
    include_once 'result.php';
    exit;
}

// Prepare the sticky form.
if (isset(\$_GET['tryagain'])) {
    (float)\$amount = isset(\$_SESSION['amount']) ? number_format((float)\$_SESSION['amount'] / 100, 2, '.', '') : '';
    \$email     = isset(\$_SESSION['email']) ? \$_SESSION['email'] : '';
    \$reference = isset(\$_SESSION['reference']) ? \$_SESSION['reference'] : '';
    \$currency  = isset(\$_SESSION['currency']) ? \$_SESSION['currency'] : '';
} else {
    \$amount    = '';
    \$email     = '';
    \$reference = '';
    \$currency  = '';
}
\$today_formatted = (string)\$today->format('Y-m-d H:i:s');

//Get currencies
\$currency_options = '';
\$currencies       = PayGate_Currencies::getCurrencies(\$permitted_currencies);
foreach (\$currencies as \$code) {
    if (\$code['CurrencyCode'] == \$currency) {

        \$currency_options .= '<option value="' . \$code['CurrencyCode'] . '" selected>' . \$code['CurrencyCode'] . ' - ' . \$code['Currency'] . '</option>';
    } else {
        \$currency_options .= '<option value="' . \$code['CurrencyCode'] . '">' . \$code['CurrencyCode'] . ' - ' . \$code['Currency'] . '</option>';
    }
}
echo <<<EOT
                <h2>Create Transaction</h2>
                    <form action="redirect" method="post" name="paygate_initiate_form">
                        <div class="form-group">
                            <label id="form-labels" for="AMOUNT">Amount</label>
                            <input class="form-control" type="number" id="AMOUNT" placeholder="0.00" required name="AMOUNT" min="5"
                                   value="\$amount" step="0.01" title="AMOUNT" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                this.style.border=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'inherit':'1px solid red'
                ">
                        </div>
                        <div class="form-group">
                            <label id="form-labels" for="EMAIL">Email</label>
                            <input class="form-control" type="email" name="EMAIL" id="EMAIL" value="\$email" required/>
                        </div>
                        <div class="form-group">
                            <label id="form-labels" for="REFERENCE">Reference</label>
                            <input class="form-control" type="text" name="REFERENCE" id="REFERENCE" value="\$reference" required/>
                        </div>
                        <div class="form-group">
                            <label id="form-labels" for="CURRENCY">Currency</label>
                            <select class="form-control" name="CURRENCY" id="CURRENCY">
                        \$currency_options
                            </select>
                        </div>
                        <input type="hidden" name="RETURN_URL" id="RETURN_URL" value="\$url/result"/>
                        <input type="hidden" name="TRANSACTION_DATE" id="TRANSACTION_DATE" value="\$today_formatted"/>
                        <input type="hidden" name="LOCALE" id="LOCALE" value="en-za" hidden/>
                        <input type="hidden" name="COUNTRY" id="COUNTRY" value="ZAF" hidden/>
                        <input type="submit" name="btnSubmit" class="btn btn-success btn-block" id="check-sum" value="Pay Now"/>
                        <input type="hidden" name="submitted" value="TRUE"/>

                    </form>
EOT;
include_once "includes/footer.php";
PAGE;
}
