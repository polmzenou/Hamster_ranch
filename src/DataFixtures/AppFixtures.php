<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use App\Entity\Hamster;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur admin
        $admin = $this->createUser('admin@admin.com', ['ROLE_ADMIN'], 'password', 1000);
        $manager->persist($admin);

        // Créer un utilisateur normal
        $user = $this->createUser('test@test.com', ['ROLE_USER'], 'password', 500);
        $manager->persist($user);

        // Créer 4 hamsters pour l'utilisateur normal
        $genders = ['m','m','f','f'];
        foreach ($genders as $i => $g) {
            $h = new Hamster();
            $h->setName('Hamster'.$i);
            $h->setGender($g);
            $h->setHunger(100);
            $h->setAge(0);
            $h->setActive(true);
            $h->setOwner($user);
            $manager->persist($h);
        }

        // Créer 2 hamsters pour l'admin (1 mâle, 1 femelle)
        $adminGenders = ['m', 'f'];
        foreach ($adminGenders as $i => $g) {
            $h = new Hamster();
            $h->setName('AdminHamster'.$i);
            $h->setGender($g);
            $h->setHunger(100);
            $h->setAge(0);
            $h->setActive(true);
            $h->setOwner($admin);
            $manager->persist($h);
        }

        $manager->flush();
    }

    public function createUser(string $email, array $roles, string $password, int $gold = 500): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(json_encode($roles));
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setGold($gold);
        return $user;
    }
}
