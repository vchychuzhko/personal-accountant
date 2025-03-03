<?php

namespace App\Command;

use App\Controller\Admin\DashboardController;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsCommand(
    name: 'app:sample-data',
    description: 'Deploy sample data for demo store: Currencies, Balances, Tags, Incomes, Payments, Exchanges, Deposits, Loans.'
)]
class SampleData extends Command
{
    private const TABLES_TO_TRUNCATE = ['currency', 'balance', 'tag', 'income', 'payment', 'exchange', 'deposit', 'investment', 'loan'];

    private const CURRENCY_SAMPLE_DATA = [
        ['name' => 'Dollar', 'code' => 'USD', 'rate' => 1, 'format' => '$%1'],
        ['name' => 'Euro', 'code' => 'EUR', 'rate' => 0.9633401134, 'format' => '€%1'],
        ['name' => 'Pound Sterling', 'code' => 'GBP', 'rate' => 0.7950701225, 'format' => '£%1'],
    ];

    private const BALANCE_SAMPLE_DATA = [
        ['name' => 'Deutsche Bank', 'currency_id' => 2, 'amount' => 5230.78],
        ['name' => 'PayPal', 'currency_id' => 1, 'amount' => 1827.45],
        ['name' => 'Barclays Bank', 'currency_id' => 3, 'amount' => 3412.19],
        ['name' => 'Revolut', 'currency_id' => 2, 'amount' => 820.55],
    ];

    private const TAG_SAMPLE_DATA = [
        ['name' => 'Groceries'],
        ['name' => 'Restaurants'],
        ['name' => 'Parking'],
        ['name' => 'Entertainment'],
        ['name' => 'Rent'],
        ['name' => 'Tax'],
        ['name' => 'Investment'],
    ];

    private const INCOME_SAMPLE_DATA = [
        ['balance_id' => 1, 'name' => 'Salary', 'amount' => 2500, 'created_at' => '5 month 4 days ago 19:23:00'],
        ['balance_id' => 3, 'name' => 'eBay', 'amount' => 450, 'created_at' => '5 months 2 days ago 14:10:00'],
        ['balance_id' => 1, 'name' => 'Salary', 'amount' => 3000, 'created_at' => '4 months 3 days ago 08:45:00'],
        ['balance_id' => 4, 'name' => 'Upwork', 'amount' => 250, 'created_at' => '4 months 1 day ago 11:00:00'],
        ['balance_id' => 1, 'name' => 'Salary', 'amount' => 2700, 'created_at' => '3 months 4 days ago 17:10:00'],
        ['balance_id' => 2, 'name' => 'Amazon Return', 'amount' => 800, 'created_at' => '3 months 2 days ago 10:15:00'],
        ['balance_id' => 1, 'name' => 'Salary', 'amount' => 3200, 'created_at' => '2 months 5 days ago 12:30:00'],
        ['balance_id' => 4, 'name' => 'Upwork', 'amount' => 550, 'created_at' => '2 months 3 days ago 22:30:00'],
        ['balance_id' => 1, 'name' => 'Salary', 'amount' => 2600, 'created_at' => '1 month 6 days ago 09:00:00'],
        ['balance_id' => 1, 'name' => 'Yield from "Deutsche Savings" (#1)', 'amount' => 5.84, 'created_at' => '1 month 2 days ago 13:47:00'],
        ['balance_id' => 3, 'name' => 'eBay', 'amount' => 180, 'created_at' => '25 days ago 18:25:00'],
        ['balance_id' => 1, 'name' => 'Salary', 'amount' => 2500, 'created_at' => '8 days ago 12:04:00'],
    ];

    private const PAYMENT_SAMPLE_DATA = [
        ['balance_id' => 1, 'tag_id' => 4, 'name' => 'Aquapark', 'amount' => 120, 'created_at' => '5 month 25 days ago 10:19:00'],
        ['balance_id' => 3, 'tag_id' => 5, 'name' => 'Rent', 'amount' => 1100, 'created_at' => '5 month 18 days ago 19:23:00'],
        ['balance_id' => 1, 'tag_id' => 6, 'name' => 'Tax', 'amount' => 800, 'created_at' => '5 months 13 days ago 13:40:00'],
        ['balance_id' => 3, 'tag_id' => 1, 'name' => 'Groceries', 'amount' => 54, 'created_at' => '5 months 9 days ago 08:15:00'],
        ['balance_id' => 1, 'tag_id' => 3, 'name' => 'Parking', 'amount' => 100, 'created_at' => '5 months 4 days ago 16:05:00'],
        ['balance_id' => 4, 'tag_id' => 4, 'name' => 'Youtube Premium', 'amount' => 10, 'created_at' => '5 months 2 days ago 11:30:00'],
        ['balance_id' => 1, 'tag_id' => 2, 'name' => 'McDonalds', 'amount' => 56, 'created_at' => '5 months ago 10:55:00'],
        ['balance_id' => 3, 'tag_id' => 5, 'name' => 'Rent', 'amount' => 1100, 'created_at' => '4 months 17 days ago 14:20:00'],
        ['balance_id' => 1, 'tag_id' => 6, 'name' => 'Tax', 'amount' => 800, 'created_at' => '4 months 13 days ago 09:25:00'],
        ['balance_id' => 4, 'tag_id' => 1, 'name' => 'Groceries', 'amount' => 120, 'created_at' => '4 months 10 days ago 07:50:00'],
        ['balance_id' => 1, 'tag_id' => 3, 'name' => 'Parking', 'amount' => 100, 'created_at' => '4 months 8 days ago 15:10:00'],
        ['balance_id' => 4, 'tag_id' => 2, 'name' => 'McDonalds', 'amount' => 100, 'created_at' => '4 months 3 days ago 13:05:00'],
        ['balance_id' => 4, 'tag_id' => 4, 'name' => 'Youtube Premium', 'amount' => 10, 'created_at' => '4 months 1 day ago 07:25:00'],
        ['balance_id' => 3, 'tag_id' => 5, 'name' => 'Rent', 'amount' => 1100, 'created_at' => '3 months 21 days ago 11:45:00'],
        ['balance_id' => 1, 'tag_id' => 6, 'name' => 'Tax', 'amount' => 800, 'created_at' => '3 months 15 days ago 08:40:00'],
        ['balance_id' => 2, 'tag_id' => 2, 'name' => 'McDonalds', 'amount' => 105, 'created_at' => '3 months 9 days ago 17:30:00'],
        ['balance_id' => 1, 'tag_id' => 1, 'name' => 'Groceries', 'amount' => 100, 'created_at' => '3 months 7 days ago 09:50:00'],
        ['balance_id' => 2, 'tag_id' => 7, 'name' => 'XTB AAPL 2', 'amount' => 482.18, 'investment_id' => 1, 'created_at' => '3 months 2 days ago 07:35:00'],
        ['balance_id' => 4, 'tag_id' => 4, 'name' => 'Youtube Premium', 'amount' => 10, 'created_at' => '3 months ago 10:15:00'],
        ['balance_id' => 3, 'tag_id' => 5, 'name' => 'Rent', 'amount' => 1100, 'created_at' => '2 months 19 days ago 14:55:00'],
        ['balance_id' => 1, 'tag_id' => 6, 'name' => 'Tax', 'amount' => 800, 'created_at' => '2 months 16 days ago 16:30:00'],
        ['balance_id' => 3, 'tag_id' => 3, 'name' => 'Parking', 'amount' => 100, 'created_at' => '2 months 15 days ago 08:05:00'],
        ['balance_id' => 4, 'tag_id' => 2, 'name' => 'McDonalds', 'amount' => 50, 'created_at' => '2 months 8 days ago 17:45:00'],
        ['balance_id' => 1, 'tag_id' => 1, 'name' => 'Groceries', 'amount' => 30, 'created_at' => '2 months 6 days ago 11:20:00'],
        ['balance_id' => 4, 'tag_id' => 4, 'name' => 'Youtube Premium', 'amount' => 10, 'created_at' => '2 months 3 days ago 07:05:00'],
        ['balance_id' => 3, 'tag_id' => 5, 'name' => 'Rent', 'amount' => 1100, 'created_at' => '1 month 24 days ago 15:55:00'],
        ['balance_id' => 1, 'tag_id' => 6, 'name' => 'Tax', 'amount' => 800, 'created_at' => '1 month 21 days ago 10:35:00'],
        ['balance_id' => 1, 'tag_id' => 2, 'name' => 'McDonalds', 'amount' => 85, 'created_at' => '1 month 15 days ago 09:15:00'],
        ['balance_id' => 2, 'tag_id' => 1, 'name' => 'Groceries', 'amount' => 45, 'created_at' => '1 month 12 days ago 16:55:00'],
        ['balance_id' => 2, 'tag_id' => 7, 'name' => 'XTB NVDA 2', 'amount' => 384.08, 'investment_id' => 2, 'created_at' => '1 month 5 days ago 08:25:00'],
        ['balance_id' => 4, 'tag_id' => 4, 'name' => 'Youtube Premium', 'amount' => 10, 'created_at' => '1 month 1 day ago 13:50:00'],
        ['balance_id' => 3, 'tag_id' => 5, 'name' => 'Rent', 'amount' => 1100, 'created_at' => '28 days ago 11:05:00'],
        ['balance_id' => 2, 'tag_id' => 2, 'name' => 'McDonalds', 'amount' => 110, 'created_at' => '21 days ago 15:25:00'],
        ['balance_id' => 3, 'tag_id' => 1, 'name' => 'Groceries', 'amount' => 92, 'created_at' => '19 days ago 10:00:00'],
        ['balance_id' => 4, 'tag_id' => 3, 'name' => 'Parking', 'amount' => 15, 'created_at' => '14 days ago 14:35:00'],
        ['balance_id' => 1, 'tag_id' => 6, 'name' => 'Tax', 'amount' => 800, 'created_at' => '11 days ago 09:05:00'],
        ['balance_id' => 4, 'tag_id' => 4, 'name' => 'Youtube Premium', 'amount' => 10, 'created_at' => '7 days ago 17:10:00'],
        ['balance_id' => 2, 'tag_id' => 7, 'name' => 'XTB AAPL 0.5', 'amount' => 127.32, 'investment_id' => 1, 'created_at' => '5 days ago 07:45:00'],
        ['balance_id' => 3, 'tag_id' => 2, 'name' => 'McDonalds', 'amount' => 78, 'created_at' => '3 days ago 16:15:00'],
        ['balance_id' => 2, 'tag_id' => 1, 'name' => 'Groceries', 'amount' => 81, 'created_at' => '2 days ago 08:50:00'],
        ['balance_id' => 1, 'tag_id' => 3, 'name' => 'Parking', 'amount' => 10, 'created_at' => '1 day ago 13:25:00'],
    ];

    private const EXCHANGE_SAMPLE_DATA = [
        ['balance_from_id' => 2, 'balance_to_id' => 1, 'amount' => 200, 'result' => 190.67, 'created_at' => '5 month 4 days ago 17:58:00'],
        ['balance_from_id' => 4, 'balance_to_id' => 3, 'amount' => 250, 'result' => 205.6, 'created_at' => '4 months 1 day ago 12:26:00'],
        ['balance_from_id' => 2, 'balance_to_id' => 1, 'amount' => 600, 'result' => 575, 'created_at' => '3 months 2 days ago 12:35:00'],
        ['balance_from_id' => 4, 'balance_to_id' => 1, 'amount' => 450, 'result' => 450, 'created_at' => '1 month 2 days ago 09:14:00'],
    ];

    private const DEPOSIT_SAMPLE_DATA = [
        [
            'balance_id' => 1,
            'name' => 'Deutsche Savings',
            'amount' => 500,
            'profit' => 5.84,
            'status' => 1,
            'interest' => 5.5,
            'tax' => 15,
            'period' => 3,
            'start_date' => '4 months 2 days ago 00:00:00',
            'end_date' => '1 month 2 days ago 00:00:00',
        ],
        [
            'balance_id' => 3,
            'name' => 'Barclays Savings',
            'amount' => 1000,
            'profit' => null,
            'status' => 0,
            'interest' => 8,
            'tax' => 19,
            'period' => 6,
            'start_date' => '1 month ago 00:00:00',
            'end_date' => '+5 month 00:00:00',
        ],
    ];

    private const INVESTMENT_SAMPLE_DATA = [
        ['name' => 'AAPL', 'share' => 2.5, 'currency_id' => 1, 'price' => 263.67],
        ['name' => 'NVDA', 'share' => 2, 'currency_id' => 1, 'price' => 188.68],
    ];

    private const LOAN_SAMPLE_DATA = [
        ['currency_id' => 1, 'person' => 'Bruce', 'amount' => 1300, 'created_at' => '1 year ago 19:23:14'],
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly TagAwareCacheInterface $cache,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('no-interaction')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('This action will reset all data database, proceed? [y/N] ', false);

            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }

        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        foreach (self::TABLES_TO_TRUNCATE as $table) {
            $this->connection->executeStatement('TRUNCATE TABLE ' . $table);
        }

        $output->writeln('Tables are truncated.');

        // Currencies
        $output->write('Processing currencies...');

        foreach (self::CURRENCY_SAMPLE_DATA as $currency) {
            $this->connection->insert('currency', $currency);
        }

        $output->writeln(count(self::CURRENCY_SAMPLE_DATA) . ' records added');

        // Balances
        $output->write('Processing balances...');

        foreach (self::BALANCE_SAMPLE_DATA as $balance) {
            $this->connection->insert('balance', $balance);
        }

        $output->writeln(count(self::BALANCE_SAMPLE_DATA) . ' records added');

        // Tags
        $output->write('Processing tags...');

        foreach (self::TAG_SAMPLE_DATA as $tag) {
            $this->connection->insert('tag', $tag);
        }

        $output->writeln(count(self::TAG_SAMPLE_DATA) . ' records added');

        // Incomes
        $output->write('Processing incomes...');

        foreach (self::INCOME_SAMPLE_DATA as $income) {
            $income['created_at'] = (new \DateTime($income['created_at']))->format('Y-m-d H:i:s');

            $this->connection->insert('income', $income);
        }

        $output->writeln(count(self::INCOME_SAMPLE_DATA) . ' records added');

        // Payments
        $output->write('Processing payments...');

        foreach (self::PAYMENT_SAMPLE_DATA as $payment) {
            $payment['created_at'] = (new \DateTime($payment['created_at']))->format('Y-m-d H:i:s');

            $this->connection->insert('payment', $payment);
        }

        $output->writeln(count(self::PAYMENT_SAMPLE_DATA) . ' records added');

        // Exchanges
        $output->write('Processing exchanges...');

        foreach (self::EXCHANGE_SAMPLE_DATA as $exchange) {
            $exchange['created_at'] = (new \DateTime($exchange['created_at']))->format('Y-m-d H:i:s');

            $this->connection->insert('exchange', $exchange);
        }

        $output->writeln(count(self::EXCHANGE_SAMPLE_DATA) . ' records added');

        // Deposits
        $output->write('Processing deposits...');

        foreach (self::DEPOSIT_SAMPLE_DATA as $deposit) {
            $deposit['start_date'] = (new \DateTime($deposit['start_date']))->format('Y-m-d H:i:s');
            $deposit['end_date'] = (new \DateTime($deposit['end_date']))->format('Y-m-d H:i:s');

            $this->connection->insert('deposit', $deposit);
        }

        $output->writeln(count(self::DEPOSIT_SAMPLE_DATA) . ' records added');

        // Investments
        $output->write('Processing investments...');

        foreach (self::INVESTMENT_SAMPLE_DATA as $investment) {
            $this->connection->insert('investment', $investment);
        }

        $output->writeln(count(self::INVESTMENT_SAMPLE_DATA) . ' records added');

        // Loans
        $output->write('Processing loans...');

        foreach (self::LOAN_SAMPLE_DATA as $loan) {
            $loan['created_at'] = (new \DateTime($loan['created_at']))->format('Y-m-d H:i:s');

            $this->connection->insert('loan', $loan);
        }

        $output->writeln(count(self::LOAN_SAMPLE_DATA) . ' records added');

        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        // Cache clear
        $this->cache->invalidateTags([DashboardController::DASHBOARD_CACHE_TAG]);

        $output->writeln('Cache is cleared.');

        $output->writeln('Sample Data is imported.');

        return Command::SUCCESS;
    }
}
