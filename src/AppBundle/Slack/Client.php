<?php

namespace AppBundle\Slack;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
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

    public function getDirectMessageHistory(string $channel, int $timestamp): array
    {
        $options = [
            'channel' => $channel,
            'latest' => $timestamp,
            'inclusive' => 1,
            'count' => 2,
        ];

        return $this->get('im.history', $options);
    }

    public function getUser(string $userId): array
    {
        $options = [
            'user' => $userId,
        ];

        return $this->get('users.info', $options);
    }

    public function postMessage(string $message, string $channel)
    {
        $payload = [
            'text' => $message,
            'channel' => $channel,
            'username' => 'wilson-planning',
        ];

        $this->logger->debug(
            'Post this message {payload} to Slack',
            ['payload' => $payload]
        );

        try {
            $response = $this->client->post(
                $this->baseUri . 'chat.postMessage',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->token,
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ]
            );
        } catch (RequestException $e) {
            $this->logger->error(
                'Error from Slack {error}',
                ['error' => $e->getResponse()->getBody()->getContents()]
            );

            throw $e;
        }

        return $this->handleResponse($response);
    }

    private function get(string $uri, array $options): array
    {
        $options['query'] = array_merge(
            ['token' => $this->token],
            $options
        );

        try {
            return $this->handleResponse(
                $this->client->get($this->baseUri . $uri, $options)
            );
        } catch (RequestException $e) {
            $this->logger->error(
                'Error from Slack {error}',
                ['error' => $e->getResponse()->getBody()->getContents()]
            );

            throw $e;
        }
    }

    private function handleResponse(ResponseInterface $response): array
    {
        $data = json_decode($response->getBody()->getContents(), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException("Can't get Slack response");
        }

        if (!isset($data['ok']) || true !== $data['ok']) {
            $this->logger->error(
                'Error from Slack {error}',
                ['error' => $data]
            );

            throw new \RuntimeException('Got error from Slack');
        }

        $this->logger->debug('Response from Slack {data}', ['data' => $data]);

        return $data;
    }
}
