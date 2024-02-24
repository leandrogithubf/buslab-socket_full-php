<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="event")
 */
class Event extends AbstractBaseEntity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=15)
     */
    protected $identifier;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $comment;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $action;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $start;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $end;

    /**
     * @ORM\ManyToOne(targetEntity=Vehicle::class)
     * @ORM\JoinColumn(name="vehicle_id", referencedColumnName="id")
     */
    protected $vehicle;

    /**
     * @ORM\ManyToOne(targetEntity=EventModality::class)
     * @ORM\JoinColumn(nullable=false)
     */
    protected $modality;

    /**
     * @ORM\ManyToOne(targetEntity=EventStatus::class)
     * @ORM\JoinColumn(nullable=false)
     */
    protected $status;

    /**
     * @ORM\ManyToOne(targetEntity=EventCategory::class)
     * @ORM\JoinColumn(nullable=false)
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity=Line::class)
     * @ORM\JoinColumn(name="line_id", referencedColumnName="id")
     */
    protected $line;

    /**
     * @ORM\ManyToOne(targetEntity=Employee::class)
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="id")
     */
    protected $employee;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected $partial;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $tableRef;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected $garage;

    /**
     * @ORM\Column(type="string", length=14)
     */
    protected $protocol;

    /**
     * @ORM\ManyToOne(targetEntity=Trip::class, inversedBy="events")
     * @ORM\JoinColumn(name="trip_id", referencedColumnName="id")
     */
    protected $trip;

    /**
     * @ORM\ManyToOne(targetEntity=Sector::class)
     * @ORM\JoinColumn(name="sector_id", referencedColumnName="id")
     */
    protected $sector;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $local;

    /**
     * @ORM\ManyToOne(targetEntity=Employee::class)
     * @ORM\JoinColumn(name="driver_id", referencedColumnName="id")
     */
    protected $driver;

    /**
     * @ORM\ManyToOne(targetEntity=Employee::class)
     * @ORM\JoinColumn(name="collector_id", referencedColumnName="id")
     */
    protected $collector;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isActive;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updatedAt;

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(?Trip $trip): self
    {
        $this->trip = $trip;

        return $this;
    }

    public function getSector(): ?Sector
    {
        return $this->sector;
    }

    public function setSector(?Sector $sector): self
    {
        $this->sector = $sector;

        return $this;
    }

    public function getLocal(): ?string
    {
        return $this->local;
    }

    public function setLocal(?string $local): self
    {
        $this->local = $local;

        return $this;
    }

    public function getDriver(): ?Employee
    {
        return $this->driver;
    }

    public function setDriver(?Employee $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    public function getCollector(): ?Employee
    {
        return $this->collector;
    }

    public function setCollector(?Employee $collector): self
    {
        $this->collector = $collector;

        return $this;
    }

    public function getProtocol(): ?string
    {
        return $this->protocol;
    }

    public function setProtocol(?string $protocol): self
    {
        $this->protocol = $protocol;

        return $this;
    }
}
