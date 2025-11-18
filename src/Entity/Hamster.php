<?php

namespace App\Entity;

use App\Repository\HamsterRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: HamsterRepository::class)]
class Hamster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['hamster:list', 'hamster:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Range(min:2)]
    #[Groups(['hamster:list', 'hamster:read', 'user:read'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\Range(min:0, max:100)]
    #[Groups(['hamster:list', 'hamster:read', 'user:read'])]
    private int $hunger = 100;

    #[ORM\Column]
    #[Assert\Range(min:0, max:500)]
    #[Groups(['hamster:list', 'hamster:read', 'user:read'])]
    private int $age = 0;

    #[ORM\Column(type:"string", length: 1)]
    #[Assert\Choice(choices:["m","f"])]
    #[Groups(['hamster:list', 'hamster:read', 'user:read'])]
    private ?string $gender = null;

    #[ORM\Column(type:"boolean")]
    #[Groups(['hamster:list', 'hamster:read', 'user:read'])]
    private ?bool $active = true;

    #[ORM\ManyToOne(inversedBy: 'hamsters')]
    #[ORM\JoinColumn(nullable:false, onDelete:"CASCADE")]
    #[Groups(['hamster:owner'])]
    private ?User $owner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getHunger(): ?int
    {
        return $this->hunger;
    }

    public function setHunger(int $hunger): static
    {
        $this->hunger = $hunger;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
