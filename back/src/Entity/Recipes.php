<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\RecipesRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RecipesRepository::class)]
#[ApiResource(
    operations: [
        // ACCÈS PUBLIC - Aperçus des recettes (page d'accueil)
        new GetCollection(
            normalizationContext: ['groups' => ['recipe:read:public']],
            security: "true" // Explicitement public - TOUJOURS autorisé
        ),
        new Get(
            normalizationContext: ['groups' => ['recipe:read:public']],
            security: "true" // Explicitement public - TOUJOURS autorisé
        ),
        
        // ACCÈS AUTHENTIFIÉ - Opérations pour utilisateurs connectés
        new Post(
            normalizationContext: ['groups' => ['recipe:read']],
            denormalizationContext: ['groups' => ['recipe:write']],
            security: "is_granted('ROLE_USER')"
        ),
        new Put(
            normalizationContext: ['groups' => ['recipe:read']],
            denormalizationContext: ['groups' => ['recipe:write']],
            security: "is_granted('ROLE_USER') and object.getUser() == user.getId()"
        ),
        new Delete(
            security: "is_granted('ROLE_USER') and object.getUser() == user.getId()"
        )
    ]
)]
class Recipes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['recipe:read', 'recipe:read:public'])] // Ajout de l'ID dans le groupe public
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['recipe:write', 'recipe:read', 'recipe:read:public'])]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['recipe:write', 'recipe:read', 'recipe:read:public'])]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['recipe:write', 'recipe:read'])] // Instructions complètes uniquement pour les utilisateurs connectés
    private ?string $instructions = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['recipe:write', 'recipe:read'])] // Ingrédients complets uniquement pour les utilisateurs connectés
    private ?array $ingredients = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['recipe:write', 'recipe:read', 'recipe:read:public'])]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['recipe:write', 'recipe:read', 'recipe:read:public'])]
    private ?int $servings = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['recipe:write', 'recipe:read', 'recipe:read:public'])]
    private ?int $cookingTime = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['recipe:read', 'recipe:read:public'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'string', length: 36)]
    #[Groups(['recipe:read'])] // User ID uniquement pour les utilisateurs connectés
    private ?string $user = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['recipe:read'])]
    private ?int $spoonacularId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->ingredients = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): self
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function getIngredients(): ?array
    {
        return $this->ingredients;
    }

    public function setIngredients(?array $ingredients): self
    {
        $this->ingredients = $ingredients;
        return $this;
    }

    public function addIngredient(array $ingredient): self
    {
        if (!in_array($ingredient, $this->ingredients ?? [])) {
            $this->ingredients[] = $ingredient;
        }
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getServings(): ?int
    {
        return $this->servings;
    }

    public function setServings(int $servings): self
    {
        $this->servings = $servings;
        return $this;
    }

    public function getCookingTime(): ?int
    {
        return $this->cookingTime;
    }

    public function setCookingTime(int $cookingTime): self
    {
        $this->cookingTime = $cookingTime;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getSpoonacularId(): ?int
    {
        return $this->spoonacularId;
    }

    public function setSpoonacularId(?int $spoonacularId): self
    {
        $this->spoonacularId = $spoonacularId;
        return $this;
    }
    
    // Méthode helper pour obtenir un aperçu de la description
    #[Groups(['recipe:read:public'])]
    public function getDescriptionPreview(): string
    {
        if (!$this->description) {
            return '';
        }
        return strlen($this->description) > 150 
            ? substr($this->description, 0, 150) . '...' 
            : $this->description;
    }
}