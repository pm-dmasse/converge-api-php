<?php

namespace markroland\Converge;

/**
 *
 * A PHP class that acts as wrapper for the Elavon Converge API
 *
 * @author Mark Roland
 * @copyright 2014 Mark Roland
 * @license http://opensource.org/licenses/MIT
 * @link http://github.com/markroland/converge-api-php
 *
 **/
class ConvergeApi
{

    /**
     * Merchant ID
     * @var string
     */
    private $merchant_id = '';

    /**
     * User ID
     * @var string
     */
    private $user_id = '';

    /**
     * Pin
     * @var string
     */
    private $pin = '';

    /**
     * API Live mode
     * @var boolean
     */
    private $live = true;

    /**
     * API Test mode
     * @var boolean
     */
    private $test = false;

    /**
     * A variable to hold debugging information
     * @var array
     */
    public $debug = array();

    /**
     * Class constructor
     *
     * @param string $merchant_id Merchant ID
     * @param string $user_id User ID
     * @param string $pin PIN
     * @param boolean $live True to use the Live server, false to use the Demo server
     * @return null
     **/
    public function __construct($merchant_id, $user_id, $pin, $live = true, $test = false)
    {
        $this->merchant_id = $merchant_id;
        $this->user_id = $user_id;
        $this->pin = $pin;
        $this->live = $live;
        $this->test = ($test ? 'true' : 'false');
    }

    /**
     * Send a HTTP request to the API
     *
     * @param string $api_method The API method to be called
     * @param string $http_method The HTTP method to be used (GET, POST, PUT, DELETE, etc.)
     * @param array $data Any data to be sent to the API
     * @param array $curlopts Any additional cURL options
     * @return string
     **/
    private function sendRequest($api_method, $http_method = 'GET', $data = null, $curlopts = array())
    {

        // Standard data
        $data['ssl_merchant_id'] = $this->merchant_id;
        $data['ssl_user_id'] = $this->user_id;
        $data['ssl_pin'] = $this->pin;
        $data['ssl_show_form'] = 'false';
        $data['ssl_result_format'] = 'ascii';
        $data['ssl_test_mode'] = $this->test;

        // Set request
        if ($this->live) {
            $request_url = 'https://www.myvirtualmerchant.com/VirtualMerchant/process.do';
        } else {
            $request_url = 'https://demo.myvirtualmerchant.com/VirtualMerchantDemo/process.do';
        }

        // Debugging output
        $this->debug = array();
        $this->debug['HTTP Method'] = $http_method;
        $this->debug['Request URL'] = $request_url;

        // Create a cURL handle
        $ch = curl_init();

        // Set the request
        curl_setopt($ch, CURLOPT_URL, $request_url);

        // Save the response to a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Set HTTP method
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);

        // This may be necessary, depending on your server's configuration
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // This may be necessary, depending on your server's configuration
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (count($curlopts)) {
            curl_setopt_array($ch, $curlopts);
        }

        // Send data
        if (!empty($data)) {

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

            // Debugging output
            $this->debug['Posted Data'] = $data;

        }

        // Execute cURL request
        $curl_response = curl_exec($ch);

        // Save CURL debugging info
        $this->debug['Last Response'] = $curl_response;
        $this->debug['Curl Info'] = curl_getinfo($ch);

        if (strlen(curl_error($ch))) {
            return array(
                'curlError' => curl_error($ch),
            );
        }

        // Close cURL handle
        curl_close($ch);

        // Parse response
        $response = $this->parseAsciiResponse($curl_response);

        // Return parsed response
        return $response;
    }

    /**
     * Parse an ASCII response
     * @param string $ascii_string An ASCII (plaintext) Response
     * @return array
     **/
    private function parseAsciiResponse($ascii_string)
    {
        $data = array();
        $lines = explode("\n", $ascii_string);
        if (count($lines)) {
            foreach ($lines as $line) {
                $kvp = explode('=', $line);
                $data[$kvp[0]] = $kvp[1];
            }
        }
        return $data;
    }

    /**
     * Submit "ccsale" request
     * @param array $parameters Input parameters
     * @return array Response from Converge
     **/
    public function ccsale(array $parameters = array(), array $curlopts = array())
    {
        $parameters['ssl_transaction_type'] = 'ccsale';
        return $this->sendRequest('ccsale', 'POST', $parameters, $curlopts);
    }

    /**
     * Submit "ccaddinstall" request
     * @param array $parameters Input parameters
     * @return array Response from Converge
     **/
    public function ccaddinstall(array $parameters = array(), array $curlopts = array())
    {
        $parameters['ssl_transaction_type'] = 'ccaddinstall';
        return $this->sendRequest('ccaddinstall', 'POST', $parameters, $curlopts);
    }
}
