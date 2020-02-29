<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

// Footer Link.
$footer_link = isset( $isTerms ) && $isTerms == true ? '<a href="' . $url . '">Create Transaction</a>' : '<a href="' . $url . '/terms">Terms and Conditions</a>';

// Logo Images.
$logo_images                                                                                   = [];
$Amex ? $logo_images['Amex']                                                                   = 'Amex' : '';
$DPO_SA ? $logo_images['DPO-SA']                                                               = 'DPO-SA' : '';
$Mastercard_Securecode ? $logo_images['Mastercard_Securecode']                                 = 'Mastercard_Securecode' : '';
$verified_by_visa ? $logo_images['verified-by-visa']                                           = 'verified-by-visa' : '';
$Diners_Club ? $logo_images['Diners-Club']                                                     = 'Diners-Club' : '';
$mastercard ? $logo_images['mastercard']                                                       = 'mastercard' : '';
$SCode ? $logo_images['SCode']                                                                 = 'SCode' : '';
$masterpass ? $logo_images['masterpass']                                                       = 'masterpass' : '';
$SiD_Secure_EFT ? $logo_images['SiD-Secure-EFT']                                               = 'SiD-Secure-EFT' : '';
$PayGate_Risk_Engine_Logo_PayProtector ? $logo_images['PayGate-Risk-Engine-Logo-PayProtector'] = 'PayGate-Risk-Engine-Logo-PayProtector' : '';
$PayGate_Certified_Developer_Seal ? $logo_images['PayGate-Certified-Developer-Seal']           = 'PayGate-Certified-Developer-Seal' : '';
$PayGate_PayPartner_Logo ? $logo_images['PayGate-PayPartner-Logo']                             = 'PayGate-PayPartner-Logo' : '';
$Visa ? $logo_images['Visa']                                                                   = 'Visa' : '';
$Visa_Checkout ? $logo_images['Visa-Checkout']                                                 = 'Visa-Checkout' : '';
$PayPal ? $logo_images['PayPal']                                                               = 'PayPal' : '';

// Prepare img HTML string.
foreach ( $logo_images as $logo_image ) {
    $logo_images[$logo_image] = '<img src="' . $url . '/assets/' . $logo_image . '.png" alt="' . $logo_image . '">';
}
$logo_images = implode( $logo_images );

// Output HTML.
echo <<<EOT
        </div>
        <div id="payment-options-logo">$logo_images
            <p id="footer-text">$footer_link</p>
        </div>
    </body>
</html>
EOT;
