<?php
// src/Command/CreateUserCommand.php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';

    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a new user')
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the user')
            ->addArgument('name', InputArgument::REQUIRED, 'The full name of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'The plain password of the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $name = $input->getArgument('name');
        $plainPassword = $input->getArgument('password');

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setRoles(['ROLE_USER']); // o el rol que quieras asignar

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln(sprintf('Usuario "%s" creado con éxito.', $email));

        return Command::SUCCESS;
    }
}
