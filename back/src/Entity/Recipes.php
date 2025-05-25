<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\RecipesRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\Uuid;


#[ORM\Entity(repositoryClass: RecipesRepository::class)]
#[ApiResource]
class Recipes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?UuidInterface $id = null;

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
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(length: 255)]
    private ?string $image_Url = null;
        public function getImage_Url(): ?string
    {
        return $this->image_Url;
    }

    // Setter pour imageUrl
    public function setImageUrl(?string $imageUrl): self
    {
        $this->image_Url = $imageUrl;

        return $this;
    }

    #[ORM\Column(length: 255)]
    private ?string $servings = null;

    public function getServings(): ?string
    {
        return $this->servings;
    }
    public function setServings(string $servings): self
    {
        $this->servings = $servings;
        return $this;
    }

    #[ORM\Column(length: 255)]

    private ?string $cookingTime = null;

    public function getCookingTime(): ?string
    {
        return $this->cookingTime;
    }
    public function setCookingTime(string $cookingTime): self
    {
        $this->cookingTime = $cookingTime;
        return $this;
    }

    #[ORM\Column(length: 255)]
    private ?string $createdAt = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $user = null;
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUser(): ?Uuid
    {
        return $this->user;
    }

    public function setUser(Uuid $user): static
    {
        $this->user = $user;

        return $this;
    }
}
