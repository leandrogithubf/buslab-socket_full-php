<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="parameter_configuration")
 */
class ParameterConfiguration extends AbstractBaseEntity
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $maxAllowed;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $minAllowed;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class)
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     */
    protected $company;

    /**
     * @ORM\ManyToOne(targetEntity=Parameter::class)
     * @ORM\JoinColumn(name="parameter_id", referencedColumnName="id")
     */
    protected $parameter;

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

    public function getMaxAllowed(): ?string
    {
        return $this->maxAllowed;
    }
}
