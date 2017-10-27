<?php

namespace AppBundle\Slack;

use Psr\Log\LoggerInterface;

class WebhookParser
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getMessage(array $content): string
    {
        if ("message" !== ($content['event']['type'] ?? null) ||
            !isset($content['event']['text'])) {

            throw new \InvalidArgumentException('Content text not supported from Slack');
        }

        $this->logger->debug(
            'Found this message {message} from Slack',
            ['message' => $content['event']['text']]
        );

        return $content['event']['text'];
    }

    public function getSenderId(array $content): string
    {
        if (!isset($content['event']['user'])) {
            throw new \InvalidArgumentException('Content user not supported from Slack');
        }

        $this->logger->debug(
            'Found this senderId {senderId} from Slack',
            ['senderId' => $content['event']['user']]
        );

        return $content['event']['user'];
    }

    public function getChannel(array $content): string
    {
        if (!isset($content['event']['channel'])) {
            throw new \InvalidArgumentException('Content channel not supported from Slack');
        }

        $this->logger->debug(
            'Found this channel {channel} from Slack',
            ['channel' => $content['event']['channel']]
        );

        return $content['event']['channel'];
    }
}
