<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Hamster;
use Doctrine\ORM\EntityManagerInterface;

class HamsterManager
{
    public function __construct(private EntityManagerInterface $em) {}

    /** Effets secondaires après chaque action */
    public function applyTransactionEffects(User $user): void
    {
        foreach ($user->getHamsters() as $h) {
            $h->setAge($h->getAge() + 5);
            $h->setHunger($h->getHunger() - 5);

            if ($h->getAge() > 500 || $h->getHunger() < 0) {
                $h->setActive(false);
            }
        }
    }

    /** Nourrir un hamster */
    public function feed(Hamster $hamster, User $user): array
    {
        $cost = 100 - $hamster->getHunger();

        if ($user->getGold() < $cost) {
            return ['error' => 'Pas assez de gold'];
        }

        $hamster->setHunger(100);
        $user->setGold($user->getGold() - $cost);

        $this->applyTransactionEffects($user);
        $this->em->flush();

        return ['success' => true];
    }

    /** Vendre un hamster */
    public function sell(Hamster $hamster, User $user): void
    {
        $user->setGold($user->getGold() + 300);

        $this->em->remove($hamster);
        $this->applyTransactionEffects($user);

        $this->em->flush();
    }

    /** Reproduction */
    public function reproduce(Hamster $h1, Hamster $h2, User $user): array
    {
        if ($h1->getGender() === $h2->getGender()) {
            return ['error' => 'Les hamsters ne sont pas de sexes opposés'];
        }

        if (!$h1->isActive() || !$h2->isActive()) {
            return ['error' => 'Les hamsters doivent être actifs'];
        }

        $baby = new Hamster();
        $baby->setName('Baby'.rand(100,999));
        $baby->setAge(0);
        $baby->setHunger(100);
        $baby->setActive(true);
        $baby->setGender(rand(0,1) ? 'm' : 'f');
        $baby->setOwner($user);

        $this->em->persist($baby);

        $this->applyTransactionEffects($user);
        $this->em->flush();

        return ['baby' => $baby];
    }
}
