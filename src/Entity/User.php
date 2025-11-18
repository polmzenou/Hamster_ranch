<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:180, unique:true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(type:"json")]
    private ?string $roles = null;

    #[ORM\Column(type:"string")]
    #[Assert\Length(min: 8)]
    private ?string $password = null;

    #[ORM\Column(type:"integer")]
    private ?int $gold = null;

    /**
     * @var Collection<int, Hamster>
     */
    #[ORM\OneToMany(targetEntity: Hamster::class, mappedBy: 'o', cascade:["persist","remove"])]
    private Collection $hamsters;

    public function __construct()
    {
        $this->hamsters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (is_string($roles)) {
            $roles = json_decode($roles, true) ?? [];
        }
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(string|array $roles): static
    {
        if (is_array($roles)) {
            $this->roles = json_encode($roles);
        } else {
            $this->roles = $roles;
        }

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getGold(): ?int
    {
        return $this->gold;
    }

    public function setGold(int $gold): static
    {
        $this->gold = $gold;

        return $this;
    }

    /**
     * @return Collection<int, Hamster>
     */
    public function getHamsters(): Collection
    {
        return $this->hamsters;
    }

    public function addHamster(Hamster $hamster): static
    {
        if (!$this->hamsters->contains($hamster)) {
            $this->hamsters->add($hamster);
            $hamster->setUserId($this);
        }

        return $this;
    }

    public function removeHamster(Hamster $hamster): static
    {
        if ($this->hamsters->removeElement($hamster)) {
            // set the owning side to null (unless already changed)
            if ($hamster->getUserId() === $this) {
                $hamster->setUserId(null);
            }
        }

        return $this;
    }
}
