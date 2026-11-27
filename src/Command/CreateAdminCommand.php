<?php

namespace App\Command;

use App\Entity\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'Username')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $username = $input->getOption('username');
        $password = $input->getOption('password');

        if ($password !== null) {
            $io->warning('Passing a password directly as a command-line argument is insecure.');
        }

        if ($username === null) {
            $usernameQuestion = new Question('Username: ');
            $username = $helper->ask($input, $output, $usernameQuestion);

            if (!is_string($username) || trim($username) === '') {
                $io->error('Username cannot be empty.');

                return Command::FAILURE;
            }
        }

        if ($password === null) {
            $passwordQuestion = new Question('Password: ');
            $passwordQuestion->setHidden(true)->setHiddenFallback(false);
            $password = $helper->ask($input, $output, $passwordQuestion);

            if (!is_string($password) || $password === '') {
                $io->error('Password cannot be empty.');

                return Command::FAILURE;
            }
        }

        $user = new Admin();
        $user->setUsername($username);
        $user->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Admin user successfully created!');

        return Command::SUCCESS;
    }
}
