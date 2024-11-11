<?php

/**
 * Paygate class for sending a request to the Paygate API
 *
 */
class PayGateRequest
{
    /**
     * @var string
     *
     * Most common errors returned will be:
     *
     * DATA_CHK    -> Checksum posted does not match the one calculated by Paygate, either due to an incorrect encryption key used or a field that has been excluded from the checksum calculation
     * DATA_PW     -> Mandatory fields have been excluded from the post to Paygate, refer to page 9 of the documentation as to what fields should be posted.
     * DATA_CUR    -> The currency that has been posted to Paygate is not supported.
     * PGID_NOT_EN -> The Paygate ID being used to post data to Paygate has not yet been enabled, or there are no payment methods setup on it.
     *
     */
    public string $lastError;
    /**
     * @var array contains the data to be posted to Paygate query service
     */
    public array $queryRequest;
    /**
     * @var array contains the data to be posted to Paygate query service
     */
    public array $initiateRequest;
    public array $initiateResponse;
    /**
     * @var string the url of the Paygate initiate page
     */
    public static string $initiateUrl = 'https://secure.paygate.co.za/payweb3/initiate.trans';
    /**
     * @var string the url of the Paygate query page
     */
    public static string $queryUrl = 'https://secure.paygate.co.za/payweb3/query.trans';
    public $queryResponse;
    public array $processRequest;

    public function __construct()
    {
        $this->initiateRequest  = [];
        $this->initiateResponse = [];
    }

    /**
     * Function to do curl post to Paygate to initiate a transaction
     *
     * @return bool
     */
    public function doInitiate(): bool
    {
        $this->initiateRequest['CHECKSUM'] = $this->generateChecksum($this->initiateRequest);

        $result = $this->doCurlPost($this->initiateRequest, self::$initiateUrl);

        if ($result !== false) {
            parse_str($result, $this->initiateResponse);
            $result = $this->processInitRes();
        }

        return $result;
    }

    /**
     * function to do actual curl post to Paygate
     *
     * @param array $postData data to be posted
     * @param string $url to be posted to
     *
     * @return bool | string
     */
    public function doCurlPost(array $postData, string $url): bool|string
    {
        $payWeb3 = new PayGatePayWeb3();

        if ($payWeb3->isCurlInstalled()) {
            $fields_string = '';

            //url-ify the data for the POST
            foreach ($postData as $key => $value) {
                $fields_string .= $key . '=' . urlencode($value) . '&';
            }
            //remove trailing '&'
            $fields_string = rtrim($fields_string, '&');

            if ($payWeb3->isDebug()) {
                error_log('Post via Curl: ' . $fields_string);
            }

            //open connection
            $curlHandle = curl_init();

            //set the url, number of POST vars, POST data
            if (!$payWeb3->isSsl()) {
                curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 1);
                curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 1);
            }
            curl_setopt($curlHandle, CURLOPT_URL, $url);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curlHandle, CURLOPT_NOBODY, false);
            curl_setopt($curlHandle, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
            curl_setopt($curlHandle, CURLOPT_POST, 1);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $fields_string);

            //execute post
            $result = curl_exec($curlHandle);

            //close connection
            curl_close($curlHandle);

            if ($payWeb3->isDebug()) {
                error_log('Return from Curl: ' . $result);
            }

            return $result;
        } else {
            $this->lastError = 'cURL is NOT installed on this server. https://php.net/manual/en/curl.setup.php';

            return false;
        }
    }

    /**
     * Function to do curl post to Paygate to query a transaction
     *
     * @return bool
     */
    public function doQuery(): bool
    {
        $this->queryRequest['CHECKSUM'] = $this->generateChecksum($this->queryRequest);

        $result = $this->doCurlPost($this->queryRequest, self::$queryUrl);

        if ($result !== false) {
            parse_str($result, $this->queryResponse);
            $result = $this->processQueryRes();
        }

        return $result;
    }

    /**
     * Function to handle response from initiate request and set error or processRequest as need be
     *
     * @return bool
     */
    public function processInitRes(): bool
    {
        if (array_key_exists('ERROR', $this->initiateResponse)) {
            $this->lastError = $this->initiateResponse['ERROR'];
            unset($this->initiateResponse);

            return false;
        }

        $this->processRequest = [
            'PAY_REQUEST_ID' => $this->initiateResponse['PAY_REQUEST_ID'],
            'CHECKSUM'       => $this->initiateResponse['CHECKSUM'],
        ];

        return true;
    }

    /**
     * Function to handle response from Query request and set error as need be
     *
     * @return bool
     */
    public function processQueryRes(): bool
    {
        if (array_key_exists('ERROR', $this->queryResponse)) {
            $this->lastError = $this->queryResponse['ERROR'];
            unset($this->queryResponse);

            return false;
        }

        return true;
    }

    /**
     * Function to generate the checksum to be passed in the initiate call. Refer to examples on Page 15 of the documentation
     *
     * @param array $postData
     *
     * @return string (md5 hash value)
     */
    public function generateChecksum(array $postData): string
    {
        $payWeb3 = new PayGatePayWeb3();

        $checksum = '';

        foreach ($postData as $value) {
            if ($value != '') {
                $checksum .= $value;
            }
        }

        $checksum .= $payWeb3->getEncryptionKey();

        if ($payWeb3->isDebug()) {
            error_log('Checksum Source: ' . $checksum);
        }

        return md5($checksum);
    }

    /**
     * @param array $queryRequest
     */
    public function setQueryRequest(array $queryRequest): void
    {
        $this->queryRequest = $queryRequest;
    }

    /**
     * @return array
     */
    public function getQueryRequest(): array
    {
        return $this->queryRequest;
    }

    /**
     * @param array $postData
     */
    public function setInitRequest(array $postData): void
    {
        $this->initiateRequest = $postData;
    }

    /**
     * function to compare checksums
     *
     * @param array $dataArray
     *
     * @return bool
     */
    public function validateChecksum(array $dataArray): bool
    {
        $returnedChecksum = $dataArray['CHECKSUM'];
        unset($dataArray['CHECKSUM']);

        $checksum = $this->generateChecksum($dataArray);

        return $returnedChecksum == $checksum;
    }
}
