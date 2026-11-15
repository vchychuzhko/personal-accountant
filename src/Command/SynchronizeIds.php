<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @deprecated
 */
#[AsCommand(
    name: 'app:sync-ids',
    description: 'Update IDs according to their created_at order',
)]
class SynchronizeIds extends Command
{
    public function __construct(
        private readonly Connection $connection,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('income', 'i', InputOption::VALUE_NONE, 'Sync IDs for income entity')
            ->addOption('payment', 'p', InputOption::VALUE_NONE, 'Sync IDs for payment entity')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entitiesToSync = [];

        $io->warning('This command is deprecated');

        if ($input->getOption('income')) {
            $entitiesToSync[] = 'income';
        }

        if ($input->getOption('payment')) {
            $entitiesToSync[] = 'payment';
        }

        if (empty($entitiesToSync)) {
            $io->error('At least one entity should be selected (-i for income, -p for payment)');

            return Command::FAILURE;
        }

        foreach ($entitiesToSync as $entityName) {
            $io->section(sprintf('Syncing IDs for "%s"', $entityName));
            $this->syncEntity($entityName, $input, $output, $io);
        }

        return Command::SUCCESS;
    }

    private function syncEntity(string $entityName, InputInterface $input, OutputInterface $output, SymfonyStyle $io): void
    {
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

            return;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion($modifiedCount . ' ' . $entityName . ' row(s) are going to be updated, proceed? [y/N] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $this->syncTable($entityName, $entities, $io);

        $io->success(sprintf('IDs for "%s" are synced!', $entityName));
    }

    private function syncTable(string $entityName, array $entities, SymfonyStyle $io): void
    {
        $tempTable = sprintf('%s_backup_%s', $entityName, date('Ymd_His'));

        try {
            $this->connection->executeStatement(sprintf('CREATE TABLE `%s` LIKE `%s`', $tempTable, $entityName));
            $this->connection->executeStatement(sprintf('INSERT INTO `%s` SELECT * FROM `%s`', $tempTable, $entityName));
            $io->writeln(sprintf('Backup created: `%s`.', $tempTable));

            $this->connection->executeStatement(sprintf('TRUNCATE TABLE `%s`', $entityName));
            $io->writeln('Table is truncated.');

            foreach ($entities as $entity) {
                $this->connection->insert($entityName, $entity);
            }

            $this->connection->executeStatement(sprintf('DROP TABLE `%s`', $tempTable));
            $io->writeln('Data successfully inserted. Backup removed.');
        } catch (\Throwable $e) {
            $io->warning(sprintf('Aborting due to error, original data is preserved: `%s`', $tempTable));

            throw $e;
        }
    }
}
