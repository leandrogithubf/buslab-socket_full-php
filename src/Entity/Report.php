<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="report")
 */
class Report extends AbstractBaseEntity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity=ReportType::class, inversedBy="report")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     */
    protected $type;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    protected $consumption;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    protected $consumptionReal;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    protected $speedAverage;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    protected $speedMax;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $distance;

    /**
     * @ORM\OneToMany(targetEntity=Trip::class, mappedBy="report")
     */
    protected $trip;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $average_speed_stops;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $trip_time;

    public function __construct()
    {
        $this->trip = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?ReportType
    {
        return $this->type;
    }

    public function setType(?ReportType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getConsumption(): ?string
    {
        return $this->consumption;
    }

    public function setConsumption(?string $consumption): self
    {
        $this->consumption = $consumption;

        return $this;
    }

    public function getConsumptionReal(): ?string
    {
        return $this->consumptionReal;
    }

    public function setConsumptionReal(?string $consumptionReal): self
    {
        $this->consumptionReal = $consumptionReal;

        return $this;
    }

    public function getSpeedAverage(): ?string
    {
        return $this->speedAverage;
    }

    public function setSpeedAverage(?string $speedAverage): self
    {
        $this->speedAverage = $speedAverage;

        return $this;
    }

    public function getSpeedMax(): ?string
    {
        return $this->speedMax;
    }

    public function setSpeedMax(?string $speedMax): self
    {
        $this->speedMax = $speedMax;

        return $this;
    }

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(?int $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    /**
     * @return Collection|Trip[]
     */
    public function getTrip(): Collection
    {
        return $this->trip;
    }

    public function addTrip(Trip $trip): self
    {
        if (!$this->trip->contains($trip)) {
            $this->trip[] = $trip;
            $trip->setReport($this);
        }

        return $this;
    }

    public function removeTrip(Trip $trip): self
    {
        if ($this->trip->contains($trip)) {
            $this->trip->removeElement($trip);
            // set the owning side to null (unless already changed)
            if ($trip->getReport() === $this) {
                $trip->setReport(null);
            }
        }

        return $this;
    }

    public function getAverageSpeedStops(): ?float
    {
        return $this->average_speed_stops;
    }

    public function setAverageSpeedStops(?float $average_speed_stops): self
    {
        $this->average_speed_stops = $average_speed_stops;

        return $this;
    }

    public function getTripTime(): ?\DateTimeInterface
    {
        return $this->trip_time;
    }

    public function setTripTime(?\DateTimeInterface $trip_time): self
    {
        $this->trip_time = $trip_time;

        return $this;
    }
}
