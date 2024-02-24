<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vehicle_model")
 */
class VehicleModel extends AbstractBaseEntity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=120)
     */
    protected $description;

    /**
     * Default value of 0.65.
     *
     * @ORM\Column(type="float")
     */
    protected $efficiency = 0.65;

    /**
     * Default value of 29.
     *
     * @ORM\Column(type="float")
     */
    protected $airFuelRatio = 29;

    /**
     * Default value of 832.
     *
     * @ORM\Column(type="float")
     */
    protected $fuelDensity = 832;

    /**
     * @ORM\OneToMany(targetEntity=Vehicle::class, mappedBy="model")
     */
    protected $vehicles;

    /**
     * Default value of 4.8.
     *
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    protected $volume = 4.8;

    /**
     * Default value of 95.0.
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $ect = 95.0;

    /**
     * Default value of 99.0.
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $iat = 99.0;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
    }

    public function getLabel(): string
    {
        return $this->getBrand()->getDescription() . ' - ' . $this->getDescription();
    }

    /**
     * @return Collection|Vehicle[]
     */
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): self
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles[] = $vehicle;
            $vehicle->setModel($this);
        }

        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): self
    {
        if ($this->vehicles->contains($vehicle)) {
            $this->vehicles->removeElement($vehicle);
            // set the owning side to null (unless already changed)
            if ($vehicle->getModel() === $this) {
                $vehicle->setModel(null);
            }
        }

        return $this;
    }

    public function getEct(): ?float
    {
        return $this->ect;
    }

    public function setEct(?float $ect): self
    {
        $this->ect = $ect;

        return $this;
    }

    public function getIat(): ?float
    {
        return $this->iat;
    }

    public function setIat(?float $iat): self
    {
        $this->iat = $iat;

        return $this;
    }
}
