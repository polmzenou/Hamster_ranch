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
        // Si le hamster a déjà 100 de hunger, pas besoin de le nourrir
        if ($hamster->getHunger() >= 100) {
            return ['error' => 'Le hamster n\'a pas faim'];
        }

        // Calculer le coût AVANT d'appliquer les effets
        $initialHunger = $hamster->getHunger();
        $cost = 100 - $initialHunger;

        // Le coût ne peut pas être négatif
        if ($cost < 0) {
            $cost = 0;
        }

        if ($user->getGold() < $cost) {
            return ['error' => 'Pas assez de gold'];
        }

        // Appliquer les effets de transaction à tous les hamsters
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
        // Appliquer les effets de transaction AVANT de supprimer le hamster
        // (pour que le hamster vendu ne vieillisse pas)
        foreach ($user->getHamsters() as $h) {
            // Exclure le hamster vendu des effets
            if ($h->getId() === $hamster->getId()) {
                continue;
            }
            $h->setAge($h->getAge() + 5);
            $h->setHunger($h->getHunger() - 5);

            if ($h->getAge() > 500 || $h->getHunger() < 0) {
                $h->setActive(false);
            }
        }

        // Ajouter 300 gold à l'utilisateur
        $user->setGold($user->getGold() + 300);

        // Supprimer le hamster de l'inventaire
        $this->em->remove($hamster);

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
