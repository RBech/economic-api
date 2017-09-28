<?php

namespace RBech\Economic;

class Economic
{
    private $appSecret;
    private $grantToken;
    private $baseUrl = 'https://restapi.e-conomic.com/';

    const TIMEOUT = 10;

    private $requestSuccessful = false;
    private $lastError         = '';
    private $errors            = [];
    private $lastResponse      = [];
    private $lastRequest       = [];

    /**
     * Economic constructor.
     *
     * @param $appSecret
     * @param $grantToken
     */
    public function __construct($appSecret, $grantToken)
    {
        $this->appSecret = $appSecret;
        $this->grantToken = $grantToken;
        $this->lastResponse = [
            'headers' => null,
            'body' => null
        ];
    }

    /**
     * Get last error
     *
     * @return bool
     */
    public function getLastError()
    {
        return $this->lastError ?: false;
    }

    /**
     * Get array of errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get last response
     *
     * @return mixed
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Get last request
     *
     * @return mixed
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Get last request status
     *
     * @return bool
     */
    public function success()
    {
        return $this->requestSuccessful;
    }

    /**
     * Perform a DELETE request
     *
     * @param $endpoint
     * @param array $args
     * @param int $timeout
     * @return mixed
     */
    public function delete($endpoint, $args = [], $timeout = self::TIMEOUT)
    {
        return $this->request('delete', $endpoint, $args, $timeout);
    }

    /**
     * Perform a GET request
     *
     * @param $endpoint
     * @param array $args
     * @param int $timeout
     * @return mixed
     */
    public function get($endpoint, $args = [], $timeout = self::TIMEOUT)
    {
        return $this->request('get', $endpoint, $args, $timeout);
    }

    /**
     * Perform a PATCH request
     *
     * @param $endpoint
     * @param array $args
     * @param int $timeout
     * @return mixed
     */
    public function patch($endpoint, $args = [], $timeout = self::TIMEOUT)
    {
        return $this->request('patch', $endpoint, $args, $timeout);
    }

    /**
     * Perform a POST request
     *
     * @param $endpoint
     * @param array $args
     * @param int $timeout
     * @return mixed
     */
    public function post($endpoint, $args = [], $timeout = self::TIMEOUT)
    {
        return $this->request('post', $endpoint, $args, $timeout);
    }

    /**
     * Perform a PUT request
     *
     * @param $endpoint
     * @param array $args
     * @param int $timeout
     * @return mixed
     */
    public function put($endpoint, $args = [], $timeout = self::TIMEOUT)
    {
        return $this->request('put', $endpoint, $args, $timeout);
    }

    /**
     * Perform an API request request
     *
     * @param $verb
     * @param $endpoint
     * @param array $args
     * @param int $timeout
     * @return mixed
     * @throws \Exception
     */
    private function request($verb, $endpoint, $args = [], $timeout = self::TIMEOUT)
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new \Exception("cURL support is required, but can't be found.");
        }

        $url = $this->baseUrl . $endpoint;

        $response = $this->prepareStateForRequest($verb, $endpoint, $url, $timeout);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-AppSecretToken: ' . $this->appSecret,
            'X-AgreementGrantToken: ' . $this->grantToken,
        ));

        curl_setopt($ch, CURLOPT_USERAGENT, 'RBech/Economic-API (https://github.com/RBech/economic-api)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);


        switch ($verb) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                $this->attachRequestBody($ch, $args);
                break;
            case 'get':
                $query = http_build_query($args, '', '&');
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
                break;
            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'patch':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                $this->attachRequestBody($ch, $args);
                break;
            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->attachRequestBody($ch, $args);
                break;
        }

        $responseContent     = curl_exec($ch);
        $response['headers'] = curl_getinfo($ch);
        $response            = $this->setResponseState($response, $responseContent, $ch);
        $formattedResponse   = $this->formatResponse($response);
        curl_close($ch);

        $this->isSuccessful($response, $formattedResponse, $timeout);
        return $formattedResponse;
    }

    /**
     * json_encode data and attach it to the request
     *
     * @param $ch
     * @param $data
     */
    private function attachRequestBody(&$ch, $data)
    {
        $encoded = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }

    /**
     * Reset state prior to request
     *
     * @param $verb
     * @param $endpoint
     * @param $url
     * @param $timeout
     * @return array
     */
    private function prepareStateForRequest($verb, $endpoint, $url, $timeout)
    {
        $this->lastError = '';
        $this->errors = [];

        $this->requestSuccessful = false;

        $this->lastResponse = [
            'headers'     => null, // array of details from curl_getinfo()
            'httpHeaders' => null, // array of HTTP headers
            'body'        => null // content of the response
        ];

        $this->lastRequest = [
            'method'   => $verb,
            'endpoint' => $endpoint,
            'url'      => $url,
            'body'     => '',
            'timeout'  => $timeout,
        ];

        return $this->lastResponse;
    }

    /**
     * Set response state
     *
     * @param $response
     * @param $responseContent
     * @param $ch
     * @return mixed
     */
    private function setResponseState($response, $responseContent, $ch)
    {
        if ($responseContent === false) {
            $this->lastError = curl_error($ch);
        } else {
            $headerSize = $response['headers']['header_size'];

            $response['httpHeaders'] = $this->getHeadersAsArray(substr($responseContent, 0, $headerSize));
            $response['body'] = substr($responseContent, $headerSize);
            if (isset($response['headers']['request_header'])) {
                $this->lastRequest['headers'] = $response['headers']['request_header'];
            }
        }

        return $response;
    }

    /**
     * Parse header string and return array of headers
     *
     * @param $headerString
     * @return array
     */
    private function getHeadersAsArray($headerString)
    {
        $headers = [];

        foreach (explode("\r\n", $headerString) as $i => $line) {
            if ($i === 0) { // HTTP code
                continue;
            }

            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            list($key, $value) = explode(': ', $line);

            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * json_decode response body
     *
     * @param $response
     * @return bool|mixed
     */
    private function formatResponse($response)
    {
        $this->lastResponse = $response;

        if (!empty($response['body'])) {
            return json_decode($response['body']);
        }

        return false;
    }

    /**
     * Determine if request succeeded
     *
     * @param $response
     * @param $formattedResponse
     * @param $timeout
     */
    private function isSuccessful($response, $formattedResponse, $timeout)
    {
        $status = 410;

        //Get HTTP status code
        if (!empty($response['headers']) && isset($response['headers']['http_code'])) {
            $status = $response['headers']['http_code'];
        } elseif (!empty($response['body']) && isset($formattedResponse->httpStatusCode)) {
            $status = $formattedResponse->httpStatusCode;
        }

        if ($status >= 200 && $status <= 299) {
            $this->requestSuccessful = true;
            return true;
        }

        if (isset($formattedResponse->message)) {
            $this->lastError = sprintf('%d: %s', $formattedResponse->httpStatusCode, $formattedResponse->message);
            
            if (isset($formattedResponse->errors)) {
                $this->errors = $formattedResponse->errors;
            }
            return false;
        }

        return false;
    }
}
