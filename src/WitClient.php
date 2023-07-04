<?php
/**
 * Created by PhpStorm.
 * User: hazelcodes
 * Date: 6/20/23
 * Time: 10:34 AM
 */

namespace Hazelveek\PhpWitAi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Config;

class WitClient
{
    protected $guzzleClient;
    protected $witAiToken;
    protected $witAiVersion;
    protected $context;

    // Local date and time of the user in ISO8601 format (more specifically, RFC3339. Do not use UTC time, which would defeat the purpose of this field. Example: "2014-10-30T12:18:45-07:00"
    const CONTEXT_REFERENCE_TIME = 'reference_time';

    // Local timezone of the user. Must be a valid IANA timezone. Used only if no reference_time is provided. In this case, we will compute reference_time from timezone and the UTC time of the API server. If neither reference_time nor timezone are provided (or a fortiori if no context at all is provided), we will use the default timezone of your app, which you can set in 'Settings' in the web console. Example: "America/Los_Angeles"
    const CONTEXT_TIMEZONE = 'timezone';

    // Locale of the user. The first 2 letters must be a valid ISO639-1 language, followed by an underscore, followed by a valid ISO3166 alpha2 country code. locale is used to resolve the entities powered by our open-source linguistic parser, Duckling (e.g. wit/datetime, wit/amount_of_money). If you have locale-specific needs for dates and times, please contribute directly to Duckling. If a locale is not yet available in Duckling, it will default to the "parent" language, with no locale-specific customization. Example: "en_GB".
    const CONTEXT_LOCALE = 'locale';

    // Coordinates of the user. Must be in the form of an object with {"lat": float, "long": float}. coords is used to improve ranking for wit/location's resolved values. Learn more here. Example: {"lat": 37.47104, "long": -122.14703}
    const CONTEXT_COORDS = 'coords';

    public function __construct($context = null)
    {
        $this->context = $context;
        $this->witAiVersion = Config::get('witai.api_version', NULL);
        $this->witAiToken = Config::get('witai.server_auth_token', NULL);
        $this->guzzleClient = new Client([
            'base_uri' => 'https://api.wit.ai',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->witAiToken,
                'Accept-Version' => $this->witAiVersion
            ],
        ]);
    }

    /**
     * @param array $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }


    /**
     * Returns the extracted meaning from a sentence, based on the app data
     *
     * @param string        $message    User's query, between 0 and 280 characters.
     * @param array|null    $entities   Reference https://wit.ai/docs/http/#dynamic_entities_link
     * @param string|null   $tag        A comma-separated string of tags you want to use for the query
     * @param int|null      $n          The maximum number of best intents and traits you want to get back Min: 1, Max:8.
     *
     * @return array
     * @throws WitIntegrationException
     */
    public function message($message, $entities = null, $tag = null, $n = null)
    {
        $queryParams = ['q' => $message];

        if ($entities !== null) {
            $queryParams['entities'] = $entities;
        }

        if ($tag !== null) {
            $queryParams['tag'] = $tag;
        }

        if ($n !== null) {
            $queryParams['n'] = $n;
        }

        try {
            return $this->sendRequest('GET', '/message', $queryParams);

        } catch (\Exception $e) {
            throw new WitIntegrationException('An error occurred while processing the message');
        }
    }

    /**
     * Returns the meaning extracted from an audio file or stream
     *
     * @param string        $audioFile  A path to an audio file.
     * @param array|null    $entities   Reference https://wit.ai/docs/http/#dynamic_entities_link
     * @param string|null   $tag        A comma-separated string of tags you want to use for the query
     * @param int|null      $n          The maximum number of best intents and traits you want to get back Min: 1, Max:8.
     *
     * @return array
     */
    public function speech($audioFile, $entities = null, $tag = null, $n = null)
    {
        $queryParams = [];
        if ($entities !== null) {
            $queryParams['entities'] = $entities;
        }

        if ($tag !== null) {
            $queryParams['tag'] = $tag;
        }

        if ($n !== null) {
            $queryParams['n'] = $n;
        }

        return $this->sendRequest('POST', '/speech', $queryParams, null, $audioFile);
    }

    /**
     * @param array $urlParameters
     * @return string
     */
    private function buildUrl($urlParameters)
    {
        if(!empty($this->context)){
            $urlParameters['context'] = $this->context;
        }
        $finalQueryString = '';
        foreach ($urlParameters as $parameterKey => $parameterValue) {
            $urlEncodedValue = urlencode(json_encode($parameterValue));
            $finalQueryString .= "$parameterKey={$urlEncodedValue}";
        }
        return $finalQueryString;
    }

    /**
     * @param string        $httpMethod         HTTP method
     * @param string        $url                A relative path to append to the base path of the guzzleClient
     * @param array|null    $queryParameters    Associative array of query string values to add to the request.
     * @param array|null    $requestData        Associative array of data to be added as JSON to the request
     * @param string|null   $audioFilePath      A path to an audio file
     *
     * @return array
     */
    private function sendRequest($httpMethod, $url, $queryParameters = null, $requestData = null, $audioFilePath = null)
    {
        try {
            $requestOptions = [];

            if (!empty($queryParameters)) {
                $requestOptions[RequestOptions::QUERY] = $this->buildUrl($queryParameters);
            }

            if (!empty($requestData)) {
                $requestOptions[RequestOptions::JSON] = $requestData;
            }

            if (!empty($audioFilePath)) {
                $stream = fopen($audioFilePath, 'r');
                $requestOptions[RequestOptions::BODY] = $stream;
                $requestOptions[RequestOptions::HEADERS] = [
                    'Content-Type' => mime_content_type($audioFilePath),
                ];
            }

            $responseInstance = $this->guzzleClient->request($httpMethod, $url, $requestOptions);

            $responseBody = json_decode($responseInstance->getBody()->getContents(), true);
            return [
                'status_code' => $responseInstance->getStatusCode(),
                'response' => $responseBody
            ];
        } catch (ClientException $e) {
            $responseInstance = $e->getResponse();
            $errorData = json_decode($responseInstance->getBody()->getContents(), true);
            return [
                'status_code' => $responseInstance->getStatusCode(),
                'response' => $errorData
            ];
        } catch (GuzzleException $e) {
            // Handle the Guzzle exception
            $statusCode = 500;
            $errorData = 'GuzzleException: Unexpected error occurred.';

            if ($e instanceof RequestException && $e->hasResponse()) {
                // RequestException with response
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $errorData = json_decode($response->getBody()->getContents(), true);
            } elseif ($e instanceof RequestException && !$e->hasResponse()) {
                $errorData = 'RequestException: No response received.';
            }

            return [
                'status_code' => $statusCode,
                'response' => $errorData
            ];
        }
    }


}