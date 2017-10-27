<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource
 * @ORM\Entity
 */
class Vacation
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date")
     * @Assert\NotBlank
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date")
     * @Assert\NotBlank
     */
    private $endDate;

    /**
     * @var Member
     *
     * @ORM\ManyToOne(targetEntity="Member")
     * @Assert\NotBlank
     */
    private $member;

    public function __construct(string $startDate, string $endDate, Member $member)
    {
        $this->startDate = new \DateTime($startDate);
        $this->endDate = new \DateTime($endDate);
        $this->member = $member;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate)
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate)
    {
        $this->endDate = $endDate;
    }

    public function setMember(Member $member)
    {
        $this->member = $member;
    }

    public function getMember(): Member
    {
        return $this->member;
    }
}
