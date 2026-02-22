<?php

namespace App\Command;

use App\Entity\Admin;
use App\Service\UserConfigurationInitializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
        private readonly UserConfigurationInitializer $configInitializer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $usernameQuestion = new Question('Username: ');
        $passwordQuestion = new Question('Password: ');
        $passwordQuestion->setHidden(true)->setHiddenFallback(false);

        $username = $helper->ask($input, $output, $usernameQuestion);
        $plainPassword = $helper->ask($input, $output, $passwordQuestion);

        $user = new Admin();
        $user->setUsername($username);
        $user->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->configInitializer->initialize($user);

        $io->success('Admin user successfully created!');

        return Command::SUCCESS;
    }
}
