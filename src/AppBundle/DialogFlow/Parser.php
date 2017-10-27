<?php

namespace AppBundle\DialogFlow;

use Psr\Log\LoggerInterface;

class Parser
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getSpeech(array $content): string
    {
        return $this->getFulfillment($content)['speech'];
    }

    public function getMessageCustomPayload(array $content): array
    {
        $messages = $this->getFulfillment($content)['messages'];

        if (!is_array($messages)) {
            throw new \InvalidArgumentException('Invalid fulfillment messages from DialogFlow');
        }

        foreach ($messages as $message) {
            if (!isset($message['type']) || 4 !== $message['type']) {
                continue;
            }

            if (!isset($message['payload']['startDate'], $message['payload']['endDate'])) {
                throw new \InvalidArgumentException('Invalid fulfillment messages payload from DialogFlow');
            }

            return $message['payload'];
        }

        throw new \InvalidArgumentException('Invalid fulfillment from DialogFlow');
    }

    private function getFulfillment(array $content): array
    {
        if (!isset($content['result']['fulfillment'])) {
            throw new \InvalidArgumentException('Invalid fulfillment content from DialogFlow');
        }

        $this->logger->debug(
            'Found fulfillment {fulfillment} from DialogFlow',
            ['fulfillment' => $content['result']['fulfillment']]
        );

        return $content['result']['fulfillment'];
    }
}
