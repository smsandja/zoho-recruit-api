<?php

namespace Humantech\Zoho\Recruit\Api\Client;

use GuzzleHttp\Psr7\Response;
use Humantech\Zoho\Recruit\Api\Formatter\RequestFormatter;
use Humantech\Zoho\Recruit\Api\Formatter\ResponseFormatter;
use Humantech\Zoho\Recruit\Api\Formatter\ResponseListFormatter;
use Humantech\Zoho\Recruit\Api\Formatter\ResponseRowFormatter;
use Humantech\Zoho\Recruit\Api\Formatter\XmlRequestDataFormatter;
use Humantech\Zoho\Recruit\Api\Unserializer\UnserializerBuilder;

class Client extends AbstractClient implements ClientInterface
{
    const API_BASE_URL = 'https://recruit.zoho.com/recruit/private/%s/%s/%s?authtoken=%s';

    const API_DEFAULT_VERSION = 2;

    const API_DEFAULT_SCOPE = 'recruitapi';

    const API_RESPONSE_FORMAT_JSON = 'json';

    const API_RESPONSE_FORMAT_XML = 'xml';

    /**
     * @var string
     */
    protected $authToken;

    /**
     * @param string $authToken
     */
    public function __construct($authToken)
    {
        $this->authToken = $authToken;
    }

    /**
     * @param  string $module
     * @param  string $method
     * @param  string $responseFormat
     * @param  array  $requestParameters
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getUri($module, $method, $responseFormat, array $requestParameters = array())
    {
        if (!$this->hasMethod($method)) {
            throw new \InvalidArgumentException(sprintf('The method %s is not registered', $method));
        }

        if (!$this->hasResponseFormat($responseFormat)) {
            throw new \InvalidArgumentException(sprintf('The response format %s is not registered', $responseFormat));
        }

        if (!isset($requestParameters['scope'])) {
            $requestParameters['scope'] = self::API_DEFAULT_SCOPE;
        }

        if (!isset($requestParameters['version'])) {
            $requestParameters['version'] = self::API_DEFAULT_VERSION;
        }

        $uri = sprintf(
            self::API_BASE_URL,
            $responseFormat,
            $module,
            $method,
            $this->getAuthToken()
        );

        return $uri . $this->generateQueryStringByRequestParams($requestParameters);
    }

    /**
     * @param  array $requestParameters
     *
     * @return string
     */
    protected function generateQueryStringByRequestParams(array $requestParameters)
    {
        return empty($requestParameters)
            ? ''
            : '&' . http_build_query($requestParameters)
        ;
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    protected function hasMethod($method)
    {
        return in_array($method, array(
            'getRecords',
            'getRecordById',
            'addRecords',
            'updateRecords',
            'getNoteTypes',
            'getRelatedRecords',
            'getFields',
            'getAssociatedJobOpenings',
            'changeStatus',
            'uploadFile',
            'downloadFile',
            'associateJobOpening',
            'uploadPhoto',
            'downloadPhoto',
            'uploadDocument',
            'getModules',
            'getAssociatedCandidates',
            'getSearchRecords',
        ));
    }

    /**
     * @param string $responseFormat
     *
     * @return bool
     */
    protected function hasResponseFormat($responseFormat)
    {
        return in_array(strtolower($responseFormat), array(
            'json',
            'xml',
        ));
    }

    /**
     * @param  Response $response
     * @param  string   $responseFormat
     *
     * @return array
     */
    protected function getUnserializedData(Response $response, $responseFormat)
    {
        return UnserializerBuilder::create($responseFormat)->unserialize(
            $response->getBody()->getContents()
        );
    }

    /**
     * @inheritdoc
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * @inheritdoc
     */
    public function getRecords($module, array $additionalParams = array(), $responseFormat = self::API_RESPONSE_FORMAT_JSON)
    {
        $response = $this->callApi('GET', $module, 'getRecords', $responseFormat, $additionalParams);

        return $this->getApiResponse($module, $this->getUnserializedData($response, $responseFormat));
    }

    /**
     * @inheritdoc
     */
    public function getRecordById($module, $id, array $additionalParams = array(), $responseFormat = self::API_RESPONSE_FORMAT_JSON)
    {
        $additionalParams['id'] = $id;

        $response = $this->callApi('GET', $module, 'getRecordById', $responseFormat, $additionalParams);

        return $this->getApiResponse($module, $this->getUnserializedData($response, $responseFormat));
    }

    /**
     * @inheritdoc
     */
    public function addRecords($module, $data, array $additionalParams = array(), $responseFormat = self::API_RESPONSE_FORMAT_JSON)
    {
        $formatter = new XmlRequestDataFormatter();

        $formatter = $formatter->formatter(array(
            'module' => $module,
            'data'   => $data,
        ));

        $additionalParams['xmlData'] = $formatter->getOutput();

        $response = $this->callApi('POST', $module, 'addRecords', $responseFormat, $additionalParams);

        return $this->getApiResponse($module, $this->getUnserializedData($response, $responseFormat));
    }

    /**
     * @inheritdoc
     */
    public function updateRecords($module, $id, $data, array $additionalParams = array(), $responseFormat = self::API_RESPONSE_FORMAT_JSON)
    {
        $formatter = new XmlRequestDataFormatter();

        $formatter = $formatter->formatter(array(
            'module' => $module,
            'data'   => $data,
        ));

        $additionalParams['id']      = $id;
        $additionalParams['xmlData'] = $formatter->getOutput();

        $response = $this->callApi('POST', $module, 'updateRecords', $responseFormat, $additionalParams);

        return $this->getApiResponse($module, $this->getUnserializedData($response, $responseFormat));
    }

    /**
     * @inheritdoc
     */
    public function getNoteTypes(array $additionalParams = array(), $responseFormat = self::API_RESPONSE_FORMAT_JSON)
    {
        $module = 'Notes';

        $response = $this->callApi('GET', $module, 'getNoteTypes', $responseFormat, $additionalParams);

        return $this->getApiResponse($module, $this->getUnserializedData($response, $responseFormat));
    }

    /**
     * @inheritdoc
     */
    public function getRelatedRecords($parentModule, $id, array $additionalParams = array(), $responseFormat = self::API_RESPONSE_FORMAT_JSON)
    {
        $module = 'Notes';

        $additionalParams['parentModule'] = $parentModule;
        $additionalParams['id']           = $id;

        $response = $this->callApi('GET', $module, 'getRelatedRecords', $responseFormat, $additionalParams);

        return $this->getApiResponse($module, $this->getUnserializedData($response, $responseFormat));
    }
}
