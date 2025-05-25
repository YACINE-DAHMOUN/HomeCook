<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserrecipesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserrecipesRepository::class)]
#[ApiResource]
class UserRecipes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(type: 'uuid')]
    private ?Uuid $Recipes = null;

    #[ORM\ManyToOne(inversedBy: 'userRecipes')]
    private ?user $user = null;




    public function getId(): ?int
    {
        return $this->id;
    }

   

    public function getRecipes(): ?Uuid
    {
        return $this->Recipes;
    }

    public function setRecipes(Uuid $Recipes): static
    {
        $this->Recipes = $Recipes;

        return $this;
    }

    public function getUser(): ?user
    {
        return $this->user;
    }

    public function setUser(?user $user): static
    {
        $this->user = $user;

        return $this;
    }

}
