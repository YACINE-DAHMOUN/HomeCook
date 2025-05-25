<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\IngredientsRepository;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IngredientsRepository::class)]
#[ApiResource]


class Ingredients
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?UuidInterface $id = null;
    
    private ?string $user_id = null;


    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }
    #[ORM\Column(length: 255)]
    private ?string $name = null;    

    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    #[ORM\Column(length: 255)]
    private ?string $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'ingredients')]
    private ?User $user = null;
    public function getQuantity(): ?string
    {
        return $this->quantity;
    }
    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }
    #[ORM\Column(length: 255)]
    public function getUserId(): ?string
    {
        return $this->user_id;
    }
    public function setUserId(string $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}