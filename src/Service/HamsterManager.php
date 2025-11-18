<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Hamster;
use Doctrine\ORM\EntityManagerInterface;

class HamsterManager
{
    public function __construct(private EntityManagerInterface $em) {}

    /** Effets de transaction après chaque action */
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
        // Si le hamster a déjà 100 de hunger, pas besoin de le nourrir
        if ($hamster->getHunger() >= 100) {
            return ['error' => 'Le hamster n\'a pas faim, il est rassasié !'];
        }

        // Calculer le coût avant d'appliquer les effets
        $initialHunger = $hamster->getHunger();
        $cost = 100 - $initialHunger;

        // Le coût ne peut pas être négatif, sinon on le met à 0
        if ($cost < 0) {
            $cost = 0;
        }

        if ($user->getGold() < $cost) {
            return ['error' => 'Pas assez de gold'];
        }

        // Appliquer les effets de transaction à tous les hamsters de l'utilisateur
        $this->applyTransactionEffects($user);
        
        // Maintenant nourrir le hamster (après les effets, donc il garde 100 de hunger)
        $hamster->setHunger(100);
        $user->setGold($user->getGold() - $cost);

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

        // Trouver le hamster mâle pour lui attribuer le propriétaire du bébé
        $maleHamster = $h1->getGender() === 'm' ? $h1 : $h2;
        $owner = $maleHamster->getOwner();

        if (!$owner) {
            return ['error' => 'Le hamster mâle n\'a pas de propriétaire'];
        }

        $baby = new Hamster();
        $baby->setName('Baby'.rand(100,999));
        $baby->setAge(0);
        $baby->setHunger(100);
        $baby->setActive(true);
        $baby->setGender(rand(0,1) ? 'm' : 'f');
        $baby->setOwner($owner);

        $this->em->persist($baby);

        $this->applyTransactionEffects($owner);
        $this->em->flush();

        return ['baby' => $baby];
    }
}
