<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="schedule_date")
 */
class ScheduleDate extends AbstractBaseEntity
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
     * @ORM\ManyToOne(targetEntity=Vehicle::class)
     * @ORM\JoinColumn(name="vehicle_id", referencedColumnName="id")
     */
    protected $vehicle;

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
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * @ORM\ManyToOne(targetEntity=Schedule::class, inversedBy="dates")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $schedule;

    /**
     * @ORM\OneToOne(targetEntity=Trip::class, mappedBy="scheduleDate", cascade={"persist", "remove"})
     */
    protected $trip;
}
