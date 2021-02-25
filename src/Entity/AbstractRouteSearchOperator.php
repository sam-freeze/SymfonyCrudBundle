<?php

namespace SamFreeze\SymfonyCrudBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

abstract class AbstractRouteSearchOperator
{
    /**
     *
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
	protected $route;

    /**
     * @ORM\Column(type="string", length=255)
     */
	protected $field;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
	protected $value;
	
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $userId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setField(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }
}
