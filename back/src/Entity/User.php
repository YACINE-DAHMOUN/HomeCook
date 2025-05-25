<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource]

class User implements  PasswordAuthenticatedUserInterface{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "uuid", unique: true)]
    private ?UuidInterface $id = null;
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }
    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }
    #[ORM\Column(length: 255)]
    private ?string $username = null;
    public function getUsername(): ?string
    {
        return $this->username;
    }
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }
    
    #[ORM\Column(length: 255)]
    private ?string $firstName = null;
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;
    public function getLastName(): ?string
    {
        return $this->lastName;
    }
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    #[ORM\Column(length: 255)]
    private ?string $email = null;
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }
    

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserRecipes $userrecipes = null;

    /**
     * @var Collection<int, Ingredients>
     */
    #[ORM\OneToMany(targetEntity: Ingredients::class, mappedBy: 'user')]
    private Collection $ingredients;

    /**
     * @var Collection<int, UserRecipes>
     */
    #[ORM\OneToMany(targetEntity: UserRecipes::class, mappedBy: 'user')]
    private Collection $userRecipes;

    public function __construct()
    {
        $this->ingredients = new ArrayCollection();
        $this->userRecipes = new ArrayCollection();
}

    public function getUseRrecipes(): ?UserRecipes
    {
        return $this->userrecipes;
    }

    public function setUserRecipes(?UserRecipes $userrecipes): static
    {
        $this->userrecipes = $userrecipes;

        return $this;
    }

    /**
     * @return Collection<int, Ingredients>
     */
    public function getIngredients(): Collection
    {
        return $this->ingredients;
    }

    public function addIngredient(Ingredients $ingredient): static
    {
        if (!$this->ingredients->contains($ingredient)) {
            $this->ingredients->add($ingredient);
            $ingredient->setUser($this);
        }

        return $this;
    }

    public function removeIngredient(Ingredients $ingredient): static
    {
        if ($this->ingredients->removeElement($ingredient)) {
            // set the owning side to null (unless already changed)
            if ($ingredient->getUser() === $this) {
                $ingredient->setUser(null);
            }
        }

        return $this;
    }
#[ORM\Column(length: 255)]
private ?string $password = null;

public function getPassword(): ?string
{
    return $this->password;
}

public function setPassword(string $password): self
{
    $this->password = $password;
    return $this;
}

    public function addUserRecipe(UserRecipes $userRecipe): static
    {
        if (!$this->userRecipes->contains($userRecipe)) {
            $this->userRecipes->add($userRecipe);
            $userRecipe->setUser($this);
        }

        return $this;
    }

    public function removeUserRecipe(UserRecipes $userRecipe): static
    {
        if ($this->userRecipes->removeElement($userRecipe)) {
            // set the owning side to null (unless already changed)
            if ($userRecipe->getUser() === $this) {
                $userRecipe->setUser(null);
            }
        }

        return $this;
    }

    
}
