<?php
/** @noinspection PhpMissingStrictTypesDeclarationInspection */

/*
 * Copyright (c) 2024 Payfast (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

require_once 'includes/_env.php';

/**
 * Class to do initiate and Query functions to Paygate
 *
 * @author Paygate
 * @version 0.2
 *
 */
class PayGatePayWeb3
{
    /**
     * @var string the url of the Paygate process page
     */
    public static string $processUrl = 'https://secure.paygate.co.za/payweb3/process.trans';
    /**
     * @var array contains the data to be posted to Paygate initiate
     */
    public array $initiateRequest;
    /**
     * @var array contains the response data from the initiate
     */
    public array $initiateResponse;
    private array $txnStatusArray = [
        1 => 'Approved',
        2 => 'Declined',
        4 => 'Cancelled',
    ];

    public bool $debug = false;
    public bool $ssl = false;

    /**
     * @var string (as set up on the config page in the Paygate Back Office )
     */
    public string $encryptionKey = '';

    public function __construct()
    {
        $this->initiateRequest  = [];
        $this->initiateResponse = [];
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * @return bool
     */
    public function isSsl(): bool
    {
        return $this->ssl;
    }

    /**
     * @param bool $ssl
     */
    public function setSsl(bool $ssl): void
    {
        $this->ssl = $ssl;
    }

    /**
     * @return array
     */
    public function getInitiateReq(): array
    {
        return $this->initiateRequest;
    }

    /**
     * @return string
     */
    public function getEncryptionKey(): string
    {
        // Access the global variable
        global $encryption_key;

        return $this->encryptionKey = $encryption_key;
    }

    /**
     * @param string $encryptionKey
     */
    public function setEncryptionKey(string $encryptionKey): void
    {
        $this->encryptionKey = $encryptionKey;
    }

    /**
     * @return bool
     */
    public function isCurlInstalled(): bool
    {
        if (in_array('curl', get_loaded_extensions())) {
            $isCurlInstalled = true;
        } else {
            $isCurlInstalled = false;
        }

        return $isCurlInstalled;
    }

    /**
     * returns a description of the transaction status number passed back from Paygate
     *
     * @param int $statusNumber
     *
     * @return string
     */
    public function getTxnStatusDesc(int $statusNumber): string
    {
        return $this->txnStatusArray[$statusNumber];
    }

    /**
     * Function to format date / time. php's DateTime object used to overcome limitation of standard date() function.
     * DateTime available from PHP 5.2.0
     *
     * @param string $format
     *
     * @return string
     */
    public function getDateTime(string $format): string
    {
        if (version_compare(PHP_VERSION, '5.2.0', '<')) {
            return date('Y-m-d H:i:s');
        } else {
            $dateTime = new DateTime();

            return $dateTime->format($format);
        }
    }

    /**
     * Validate the form
     *
     * @return true|void
     */
    public function validateForm()
    {
        global $recaptcha_secret;
        $referer_location = $_SERVER['HTTP_REFERER'];
        $token            = $_POST['g-recaptcha-response'];
        $action           = $_POST['action'];
        $secret           = $recaptcha_secret;

        // call curl to POST request
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query(['secret' => $secret, 'response' => $token]));
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curlHandle);
        curl_close($curlHandle);
        $arrResponse = json_decode($response, true);

        // verify the response
        if ($arrResponse['success'] == '1' && $arrResponse['action'] == $action && $arrResponse['score'] >= 0.5) {
            return true;
        } else {
            // spam submission
            header('Location:' . $referer_location);
            exit(0);
        }
    }
}
