<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

/**
 * Class to do initiate and Query functions to PayGate for PayWeb 3
 *
 * @author PayGate
 * @version 0.2
 *
 */
class PayGate_PayWeb3
{
    /**
     * @var string the url of the PayGate PayWeb 3 initiate page
     */
    public static $initiate_url = 'https://secure.paygate.co.za/payweb3/initiate.trans';
    /**
     * @var string the url of the PayGate PayWeb 3 process page
     */
    public static $process_url = 'https://secure.paygate.co.za/payweb3/process.trans';
    /**
     * @var string the url of the PayGate PayWeb 3 query page
     */
    public static $query_url = 'https://secure.paygate.co.za/payweb3/query.trans';
    /**
     * @var array contains the data to be posted to PayGate PayWeb 3 initiate
     */
    public $initiateRequest;
    /**
     * @var array contains the response data from the initiate
     */
    public $initiateResponse;
    /**
     * @var array contains the data returned from the initiate, required for the redirect of the client
     */
    public $processRequest;
    /**
     * @var array contains the data to be posted to PayGate PayWeb 3 query service
     */
    public $queryRequest;
    /**
     * @var array contains the response data from the query
     */
    public $queryResponse;
    /**
     * @var string
     *
     * Most common errors returned will be:
     *
     * DATA_CHK    -> Checksum posted does not match the one calculated by PayGate, either due to an incorrect encryption key used or a field that has been excluded from the checksum calculation
     * DATA_PW     -> Mandatory fields have been excluded from the post to PayGate, refer to page 9 of the documentation as to what fields should be posted.
     * DATA_CUR    -> The currency that has been posted to PayGate is not supported.
     * PGID_NOT_EN -> The PayGate ID being used to post data to PayGate has not yet been enabled, or there are no payment methods setup on it.
     *
     */
    public $lastError;

    private $transactionStatusArray = array(
        1 => 'Approved',
        2 => 'Declined',
        4 => 'Cancelled',
    );

    public $debug = false;
    public $ssl   = false;

    /**
     * @var string (as set up on the PayWeb 3 config page in the PayGate Back Office )
     */
    private $encryptionKey;

    public function __construct()
    {

    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @param boolean $debug
     */
    public function setDebug( $debug )
    {
        $this->debug = $debug;
    }

    /**
     * @return boolean
     */
    public function isSsl()
    {
        return $this->ssl;
    }

    /**
     * @param boolean $ssl
     */
    public function setSsl( $ssl )
    {
        $this->ssl = $ssl;
    }

    /**
     * @return array
     */
    public function getInitiateRequest()
    {
        return $this->initiateRequest;
    }

    /**
     * @param array $postData
     */
    public function setInitiateRequest( $postData )
    {
        $this->initiateRequest = $postData;
    }

    /**
     * @return array
     */
    public function getQueryRequest()
    {
        return $this->queryRequest;
    }

    /**
     * @param array $queryRequest
     */
    public function setQueryRequest( $queryRequest )
    {
        $this->queryRequest = $queryRequest;
    }

    /**
     * @return string
     */
    public function getEncryptionKey()
    {
        return $this->encryptionKey;
    }

    /**
     * @param string $encryptionKey
     */
    public function setEncryptionKey( $encryptionKey )
    {
        $this->encryptionKey = $encryptionKey;
    }

    /**
     * @return bool
     */
    public function _is_curl_installed()
    {
        if ( in_array( 'curl', get_loaded_extensions() ) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * returns a description of the transaction status number passed back from PayGate
     *
     * @param int $statusNumber
     * @return string
     */
    public function getTransactionStatusDescription( $statusNumber )
    {
        return $this->transactionStatusArray[$statusNumber];
    }

    /**
     * Function to format date / time. php's DateTime object used to overcome limitation of standard date() function.
     * DateTime available from PHP 5.2.0
     *
     * @param string $format
     * @return string
     */
    public function getDateTime( $format )
    {

        if ( version_compare( PHP_VERSION, '5.2.0', '<' ) ) {
            $dateTime = date( 'Y-m-d H:i:s' );
            return $dateTime;
        } else {
            $dateTime = new DateTime();
            return $dateTime->format( $format );
        }
    }

    /**
     * Function to generate the checksum to be passed in the initiate call. Refer to examples on Page 15 of the PayWeb3 documentation
     *
     * @param array $postData
     * @return string (md5 hash value)
     */
    public function generateChecksum( $postData )
    {
        $checksum = '';

        foreach ( $postData as $key => $value ) {
            if ( $value != '' ) {
                $checksum .= $value;
            }
        }

        $checksum .= $this->getEncryptionKey();

        if ( $this->isDebug() ) {
            error_log( 'Checksum Source: ' . $checksum, 0 );
        }

        return md5( $checksum );
    }

    /**
     * function to compare checksums
     *
     * @param array $data
     * @return bool
     */
    public function validateChecksum( $data )
    {

        $returnedChecksum = $data['CHECKSUM'];
        unset( $data['CHECKSUM'] );

        $checksum = $this->generateChecksum( $data );

        return ( $returnedChecksum == $checksum );
    }

    /**
     * Function to handle response from initiate request and set error or processRequest as need be
     *
     * @return bool
     */
    public function handleInitiateResponse()
    {
        if ( array_key_exists( 'ERROR', $this->initiateResponse ) ) {
            $this->lastError = $this->initiateResponse['ERROR'];
            unset( $this->initiateResponse );
            return false;
        }

        $this->processRequest = array(
            'PAY_REQUEST_ID' => $this->initiateResponse['PAY_REQUEST_ID'],
            'CHECKSUM'       => $this->initiateResponse['CHECKSUM'],
        );
        return true;
    }

    /**
     * Function to handle response from Query request and set error as need be
     *
     * @return bool
     */
    public function handleQueryResponse()
    {
        if ( array_key_exists( 'ERROR', $this->queryResponse ) ) {
            $this->lastError = $this->queryResponse['ERROR'];
            unset( $this->queryResponse );
            return false;
        }
        return true;
    }

    /**
     * Function to do curl post to PayGate to initiate a PayWeb 3 transaction
     *
     * @return bool
     */
    public function doInitiate()
    {

        $this->initiateRequest['CHECKSUM'] = $this->generateChecksum( $this->initiateRequest );

        $result = $this->doCurlPost( $this->initiateRequest, self::$initiate_url );

        if ( $result !== false ) {
            parse_str( $result, $this->initiateResponse );
            $result = $this->handleInitiateResponse();
        }

        return $result;
    }

    /**
     * Function to do curl post to PayGate to query a PayWeb 3 transaction
     *
     * @return bool
     */
    public function doQuery()
    {
        $this->queryRequest['CHECKSUM'] = $this->generateChecksum( $this->queryRequest );

        $result = $this->doCurlPost( $this->queryRequest, self::$query_url );

        if ( $result !== false ) {
            parse_str( $result, $this->queryResponse );
            $result = $this->handleQueryResponse();
        }

        return $result;
    }

    /**
     * function to do actual curl post to PayGate
     *
     * @param array $postData data to be posted
     * @param string $url to be posted to
     * @return bool | string
     */
    public function doCurlPost( $postData, $url )
    {

        if ( $this->_is_curl_installed() ) {

            $fields_string = '';

            //url-ify the data for the POST
            foreach ( $postData as $key => $value ) {
                $fields_string .= $key . '=' . urlencode( $value ) . '&';
            }
            //remove trailing '&'
            $fields_string = rtrim( $fields_string, '&' );

            if ( $this->isDebug() ) {
                error_log( 'Post via Curl: ' . $fields_string, 0 );
            }

            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            if ( !$this->isSsl() ) {
                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            }
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_NOBODY, false );
            curl_setopt( $ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST'] );
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );

            //execute post
            $result = curl_exec( $ch );

            //close connection
            curl_close( $ch );

            if ( $this->isDebug() ) {
                error_log( 'Return from Curl: ' . $result, 0 );
            }

            return $result;
        } else {
            $this->lastError = 'cURL is NOT installed on this server. http://php.net/manual/en/curl.setup.php';
            return false;
        }
    }
}
