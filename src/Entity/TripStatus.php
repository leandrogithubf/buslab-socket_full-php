<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="trip_status")
 */
class TripStatus extends AbstractBaseEntity
{
    public const SCHEDULED = 1;
    public const STARTED = 2;
    public const DONE = 3;
    public const NON_PRODUCTIVE = 4;

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
     * @ORM\Column(type="string", length=255)
     */
    protected $description;
}
