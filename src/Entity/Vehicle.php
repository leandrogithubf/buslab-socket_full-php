<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vehicle")
 */
class Vehicle extends AbstractBaseEntity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity=Obd::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    protected $obd;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $chassi;

    /**
     * @ORM\Column(type="integer")
     */
    protected $prefix;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="vehicles")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $company;

    /**
     * @ORM\Column(type="string", length=15)
     */
    protected $identifier;

    /**
     * @ORM\ManyToOne(targetEntity=VehicleModel::class, inversedBy="vehicles")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $model;

    /**
     * @ORM\Column(type="float")
     */
    protected $consumptionTarget;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $startOperation;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $seats;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $standing;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $periodicInspection;

    /**
     * @ORM\ManyToOne(targetEntity=VehicleStatus::class)
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     */
    private $status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getConsumptionTarget(): ?string
    {
        return $this->consumptionTarget;
    }

    public function setConsumptionTarget(?string $consumptionTarget): self
    {
        $this->consumptionTarget = $consumptionTarget;

        return $this;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
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

    public function getObd(): ?Obd
    {
        return $this->obd;
    }

    public function setObd(?Obd $obd): self
    {
        $this->obd = $obd;

        return $this;
    }

    public function getChassi(): ?string
    {
        return $this->chassi;
    }

    public function setChassi(?string $chassi): self
    {
        $this->chassi = $chassi;

        return $this;
    }

    public function getModel(): ?VehicleModel
    {
        return $this->model;
    }

    public function setModel(?VehicleModel $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getSeats(): ?int
    {
        return $this->seats;
    }

    public function setSeats(?int $seats): self
    {
        $this->seats = $seats;

        return $this;
    }

    public function getStanding(): ?int
    {
        return $this->standing;
    }

    public function setStanding(?int $standing): self
    {
        $this->standing = $standing;

        return $this;
    }

    public function getPeriodicInspection(): ?\DateTimeInterface
    {
        return $this->periodicInspection;
    }

    public function setPeriodicInspection(?\DateTimeInterface $periodicInspection): self
    {
        $this->periodicInspection = $periodicInspection;

        return $this;
    }

    public function getStatus(): ?VehicleStatus
    {
        return $this->status;
    }

    public function setStatus(?VehicleStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
}
