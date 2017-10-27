<?php

namespace AppBundle\DialogFlow;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Client
{
    private $client;
    private $baseUri;
    private $token;
    private $logger;

    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
        string $baseUri,
        string $token
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->baseUri = $baseUri;
        $this->token = $token;
    }

    public function query(string $message, string $sessionId): array
    {
        $options = [
            'query' => [
                'query' => $message,
                'sessionId' => $sessionId,
                'lang' => 'fr',
                'v' => '20170712',
            ],
        ];

        return $this->handleResponse(
            $this->call('query', $options)
        );
    }

    private function call(string $method, array $options): ResponseInterface
    {
        $options = array_merge(
            ['headers' => ['Authorization' => 'Bearer ' . $this->token]],
            $options
        );

        try {
            return $this->client->get($this->baseUri . $method, $options);
        } catch (RequestException $e) {
            $this->logger->error(
                'Error from DialogFlow {error}',
                ['error' => $e->getResponse()->getBody()->getContents()]
            );

            throw $e;
        }
    }

    private function handleResponse(ResponseInterface $response): array
    {
        $data = json_decode($response->getBody()->getContents(), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \LogicException("Can't get DialogFlow response");
        }

        return $data;
    }
}
