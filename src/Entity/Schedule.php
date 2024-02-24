<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="schedule")
 */
class Schedule extends AbstractBaseEntity
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
    protected $identifier;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     */
    protected $tableCode;

    /**
     * @ORM\Column(type="string")
     */
    protected $modality;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('WEEKDAY', 'SATURDAY', 'SUNDAY')")
     */
    protected $weekInterval;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class)
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     */
    protected $company;

    /**
     * @ORM\ManyToOne(targetEntity=Line::class)
     * @ORM\JoinColumn(name="line_id", referencedColumnName="id")
     */
    protected $line;

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
     * @ORM\ManyToOne(targetEntity=Vehicle::class)
     * @ORM\JoinColumn(name="vehicle_id", referencedColumnName="id")
     */
    protected $vehicle;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    protected $startsAt;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    protected $endsAt;

    /**
     * @ORM\Column(type="date")
     */
    protected $dataValidity;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $sequence;

    /**
     * @ORM\OneToMany(targetEntity=ScheduleDate::class, mappedBy="schedule", orphanRemoval=true)
     */
    protected $dates;
}
