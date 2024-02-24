<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="obd")
 */
class Obd
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=15)
     */
    protected $serial;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="vehicles")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $company;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $version;

    /**
     * @ORM\ManyToOne(targetEntity=CellphoneNumber::class)
     * @ORM\JoinColumn(name="cellphone_number_id", referencedColumnName="id")
     */
    protected $cellphoneNumber;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isActive;

    /**
     * @ORM\Column(type="string", length=15)
     */
    protected $identifier;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerial(): ?string
    {
        return $this->serial;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }
}
