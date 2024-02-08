<?php
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 **/

declare(strict_types=1);

namespace Macademy\Sentimate\Model;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Uri\Http;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class RapidApi
{
    public const CONFIG_PATH_API_KEY = 'macademy_sentimate/rapidapi/api_key';

    /**
     * @param GuzzleClient $guzzleClient
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param Http $http
     */
    public function __construct(
        private readonly GuzzleClient         $guzzleClient,
        private readonly LoggerInterface      $logger,
        private readonly SerializerInterface  $serializer,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface   $encryptor,
        private readonly Http                 $http
    ) {
    }

    /**
     * GetApiKey
     *
     * @return string
     */
    public function getApiKey(): string
    {
        $apiKey = $this->scopeConfig->getValue(self::CONFIG_PATH_API_KEY);
        return $this->encryptor->decrypt($apiKey);
    }

    /**
     * Generic function to call API
     *
     * @param string $endpoint
     * @param array $formParams
     * @return array
     */
    private function callApi(
        string $endpoint,
        array  $formParams = []
    ): array {
        $apiKey = $this->getApiKey();
        $url = $this->http->parse($endpoint);

        $apiHost = $url->getHost();

        try {
            $response = $this->guzzleClient->request('POST', $endpoint, [
                'form_params' => $formParams,
                'headers' => [
                    'X-RapidAPI-Host' => $apiHost,
                    'X-RapidAPI-Key' => $apiKey,
                    'content-type' => 'application/x-www-form-urlencoded',
                ],
            ]);
            $body = $response->getBody();
            $result = $this->serializer->unserialize($body);
        } catch (GuzzleException $exception) {
            $this->logger->error(__("$endpoint returned an error: %1", $exception->getMessage()));
        }

        return $result ?? [];
    }

    /**
     * Call the Sentiment Analysis API
     *
     * @param string $text
     * @return array
     */
    public function getSentimentAnalysis(
        string $text,
    ): array {
        $result = $this->callApi('https://twinword-sentiment-analysis.p.rapidapi.com/analyze/', [
            'text' => $text
        ]);

        $this->logInvalidateSentimentAnalysisResults($result);

        return $result;
    }

    /**
     * LogInvalidateSentimentAnalysisResults
     *
     * @param array $result
     * @return void
     */
    public function logInvalidateSentimentAnalysisResults(array $result): void
    {
        if (!$this->areSentimentAnalysisResultsValid($result)) {
            $stringResponse = implode(', ', $result);
            $this->logger->error(__('Sentiment Analysis API did not return expected results: %1', $stringResponse));
        }
    }

    /**
     * AreSentimentAnalysisResultsValid
     *
     * @param array $result
     * @return bool
     */
    public function areSentimentAnalysisResultsValid(
        array $result
    ): bool {
        return isset($result['type'], $result['score'], $result['ratio']);
    }
}
