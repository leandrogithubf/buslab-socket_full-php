<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="line_point")
 */
class LinePoint extends AbstractBaseEntity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="float")
     */
    protected $latitude;

    /**
     * @ORM\Column(type="float")
     */
    protected $longitude;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $address;

    /**
     * @ORM\Column(type="integer")
     */
    protected $sequence;

    /**
     * @ORM\ManyToOne(targetEntity=Line::class, inversedBy="point")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $line;

    /**
     * @ORM\Column(type="string", length=15)
     */
    protected $identifier;

    public function getLine(): ?Line
    {
        return $this->line;
    }

    public function setLine(?Line $line): self
    {
        $this->line = $line;

        return $this;
    }
}
