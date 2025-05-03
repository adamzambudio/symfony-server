<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('usuario@demo.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->hasher->hashPassword($user, '123456'));
        $manager->persist($user);

        $host = new User();
        $host->setEmail('host@demo.com');
        $host->setRoles(['ROLE_HOST']);
        $host->setPassword($this->hasher->hashPassword($host, '123456'));
        $manager->persist($host);

        $manager->flush(); // ✅ Guardar cambios en la base de datos
    }
}
