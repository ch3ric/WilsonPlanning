<?php

namespace AppBundle\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use AppBundle\Entity\Member;
use AppBundle\Slack\Client;

class MemberHandler
{
    private $registry;
    private $slackClient;
    private $logger;

    public function __construct(
        ManagerRegistry $registry,
        Client $slackClient,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->slackClient = $slackClient;
        $this->logger = $logger;
    }

    public function getOrCreateFromSlackId(string $userSlackId): Member
    {
        $slackResponse = $this->slackClient->getUser($userSlackId);

        return $this->getOrCreateFromSlackData($slackResponse);
    }

    private function getOrCreateFromSlackData(array $slackData): Member
    {
        if (!isset($slackData['user']['profile']['email'], $slackData['user']['name'])) {
            throw new \InvalidArgumentException('Invalid user data received from Slack');
        }

        $member = $this->registry
            ->getRepository(Member::class)
            ->findOneBy([
                'email' => $slackData['user']['profile']['email'],
            ]);

        if (!$member) {
            $this->logger->debug(
                'Create a new member {user}',
                ['user' => $slackData['user']]
            );

            $member = new Member(
                $slackData['user']['name'],
                $slackData['user']['profile']['email']
            );

            $em = $this->registry->getManager();
            $em->persist($member);
            $em->flush();
        }

        return $member;
    }
}
