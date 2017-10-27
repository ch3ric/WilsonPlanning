<?php

namespace AppBundle\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use AppBundle\Entity\Vacation;
use AppBundle\Entity\Member;

class VacationHandler
{
    private $registry;
    private $logger;

    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
    }

    public function create(string $startDate, string $endDate, Member $member): Vacation
    {
        $this->logger->debug(
            'Create a new vacation {startDate} {endDate} {member}',
            ['startDate' => $startDate, 'endDate' => $endDate, 'member' => (string) $member]
        );

        $vacation = new Vacation(
            $startDate,
            $endDate,
            $member
        );

        $em = $this->registry->getManager();
        $em->persist($vacation);
        $em->flush();

        return $vacation;
    }
}
