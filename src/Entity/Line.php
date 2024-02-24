<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="line")
 */
class Line extends AbstractBaseEntity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $code;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('GOING', 'RETURN', 'CIRCULATE')")
     */
    protected $direction;

    /**
     * @ORM\Column(type="string", length=15)
     */
    protected $identifier;

    /**
     * @ORM\Column(type="float")
     */
    protected $passage;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="line")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $company;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $maxSpeed;

    /**
     * @ORM\OneToMany(targetEntity=LinePoint::class, mappedBy="line", orphanRemoval=true)
     * @ORM\OrderBy({"sequence" = "ASC"})
     **/
    protected $points;

    public function __construct()
    {
        $this->points = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getMaxSpeed(): ?int
    {
        return $this->maxSpeed;
    }

    public function setMaxSpeed(?int $maxSpeed): self
    {
        $this->maxSpeed = $maxSpeed;

        return $this;
    }

    /**
     * @return Collection|LinePoint[]
     */
    public function getPoints(): Collection
    {
        return $this->points;
    }

    public function addPoint(LinePoint $point): self
    {
        if (!$this->points->contains($point)) {
            $this->points[] = $point;
            $point->setLine($this);
        }

        return $this;
    }

    public function removePoint(LinePoint $point): self
    {
        if ($this->points->contains($point)) {
            $this->points->removeElement($point);
            // set the owning side to null (unless already changed)
            if ($point->getLine() === $this) {
                $point->setLine(null);
            }
        }

        return $this;
    }
}
