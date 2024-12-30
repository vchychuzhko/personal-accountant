<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'app:fix-payment-ids', description: 'Update all payment IDs according to their created_at order.')]
class FixPaymentIds extends Command
{
    public function __construct(
        private readonly Connection $connection,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $payments = $this->connection->fetchAllAssociative('SELECT * FROM payment p ORDER BY p.created_at ASC');

        $modifiedCount = 0;

        foreach ($payments as $index => $payment) {
            if ($index + 1 !== $payment['id']) {
                $payments[$index]['id'] = $index + 1;

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

        $this->connection->executeStatement('TRUNCATE TABLE payment');
        $output->writeln('Table is truncated.');

        foreach ($payments as $payment) {
            $this->connection->insert('payment', $payment);
        }

        $output->writeln('Payment IDs are updated.');

        return Command::SUCCESS;
    }
}
