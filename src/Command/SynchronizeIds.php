<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-ids',
    description: 'Update all IDs according to their created_at order',
)]
class SynchronizeIds extends Command
{
    const ALLOWED_ENTITIES = ['income', 'payment'];

    public function __construct(
        private readonly Connection $connection,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('entity', InputArgument::REQUIRED, 'Entity to sync IDs for', null, self::ALLOWED_ENTITIES),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityName = $input->getArgument('entity');

        if (!in_array($entityName, self::ALLOWED_ENTITIES)) {
            $io->error('Entity should be one of ' . implode(', ', self::ALLOWED_ENTITIES));

            return Command::FAILURE;
        }

        $entities = $this->connection->fetchAllAssociative(sprintf('SELECT * FROM `%s` ORDER BY created_at ASC', $entityName));

        $modifiedCount = 0;

        foreach ($entities as $index => $entity) {
            if ($index + 1 !== $entity['id']) {
                $entities[$index]['id'] = $index + 1;

                $modifiedCount++;
            }
        }

        if ($modifiedCount === 0) {
            $output->writeln('No rows require update.');

            return Command::SUCCESS;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion($modifiedCount . ' row(s) are going to be updated, proceed? [y/N] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $this->connection->executeStatement(sprintf('TRUNCATE TABLE `%s`', $entityName));
        $output->writeln('Table is truncated.');

        foreach ($entities as $entity) {
            $this->connection->insert($entityName, $entity);
        }

        $io->success('IDs are synced!');

        return Command::SUCCESS;
    }
}
