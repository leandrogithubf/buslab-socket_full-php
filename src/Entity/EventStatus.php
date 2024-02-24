<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="event_status")
 */
class EventStatus extends AbstractBaseEntity
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
     * @ORM\Column(type="string", length=255)
     */
    protected $description;
}
