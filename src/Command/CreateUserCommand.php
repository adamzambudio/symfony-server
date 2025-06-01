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
            ->addArgument('password', InputArgument::REQUIRED, 'The plain password of the user')
            ->addArgument('role', InputArgument::OPTIONAL, 'Role to assign (e.g. ROLE_USER or ROLE_ADMIN)', 'ROLE_USER');
    }

     protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $name = $input->getArgument('name');
        $plainPassword = $input->getArgument('password');
        $role = strtoupper($input->getArgument('role'));

        // Validamos que el rol sea uno de los permitidos
        if (!in_array($role, ['ROLE_USER', 'ROLE_ADMIN'])) {
            $output->writeln(sprintf(
                'Rol inválido "%s". Use ROLE_USER o ROLE_ADMIN.',
                $role
            ));
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setRoles([$role]);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln(sprintf(
            'Usuario "%s" creado con éxito con rol "%s".',
            $email,
            $role
        ));

        return Command::SUCCESS;
    }
}
