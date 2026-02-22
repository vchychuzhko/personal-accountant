<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add admin_id to balance, tag, loan, investment, configuration, currency for multi-user support';
    }

    public function up(Schema $schema): void
    {
        // Add nullable admin_id columns first
        $this->addSql('ALTER TABLE balance ADD admin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tag ADD admin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE loan ADD admin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE investment ADD admin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE configuration ADD admin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE currency ADD admin_id INT DEFAULT NULL');

        // Set all existing rows to the first admin user
        $this->addSql('UPDATE balance SET admin_id = (SELECT MIN(id) FROM `admin`)');
        $this->addSql('UPDATE tag SET admin_id = (SELECT MIN(id) FROM `admin`)');
        $this->addSql('UPDATE loan SET admin_id = (SELECT MIN(id) FROM `admin`)');
        $this->addSql('UPDATE investment SET admin_id = (SELECT MIN(id) FROM `admin`)');
        $this->addSql('UPDATE configuration SET admin_id = (SELECT MIN(id) FROM `admin`)');
        $this->addSql('UPDATE currency SET admin_id = (SELECT MIN(id) FROM `admin`)');

        // Make columns NOT NULL
        $this->addSql('ALTER TABLE balance CHANGE admin_id admin_id INT NOT NULL');
        $this->addSql('ALTER TABLE tag CHANGE admin_id admin_id INT NOT NULL');
        $this->addSql('ALTER TABLE loan CHANGE admin_id admin_id INT NOT NULL');
        $this->addSql('ALTER TABLE investment CHANGE admin_id admin_id INT NOT NULL');
        $this->addSql('ALTER TABLE configuration CHANGE admin_id admin_id INT NOT NULL');
        $this->addSql('ALTER TABLE currency CHANGE admin_id admin_id INT NOT NULL');

        // Add foreign key constraints and indexes
        $this->addSql('ALTER TABLE balance ADD CONSTRAINT FK_ACF41FFE642B8210 FOREIGN KEY (admin_id) REFERENCES `admin` (id)');
        $this->addSql('CREATE INDEX IDX_ACF41FFE642B8210 ON balance (admin_id)');

        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B783642B8210 FOREIGN KEY (admin_id) REFERENCES `admin` (id)');
        $this->addSql('CREATE INDEX IDX_389B783642B8210 ON tag (admin_id)');

        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03642B8210 FOREIGN KEY (admin_id) REFERENCES `admin` (id)');
        $this->addSql('CREATE INDEX IDX_C5D30D03642B8210 ON loan (admin_id)');

        $this->addSql('ALTER TABLE investment ADD CONSTRAINT FK_43CA0AD6642B8210 FOREIGN KEY (admin_id) REFERENCES `admin` (id)');
        $this->addSql('CREATE INDEX IDX_43CA0AD6642B8210 ON investment (admin_id)');

        $this->addSql('ALTER TABLE configuration ADD CONSTRAINT FK_A5E2A5D7642B8210 FOREIGN KEY (admin_id) REFERENCES `admin` (id)');
        $this->addSql('CREATE INDEX IDX_A5E2A5D7642B8210 ON configuration (admin_id)');

        $this->addSql('ALTER TABLE currency ADD CONSTRAINT FK_6956883F642B8210 FOREIGN KEY (admin_id) REFERENCES `admin` (id)');
        $this->addSql('CREATE INDEX IDX_6956883F642B8210 ON currency (admin_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balance DROP FOREIGN KEY FK_ACF41FFE642B8210');
        $this->addSql('DROP INDEX IDX_ACF41FFE642B8210 ON balance');
        $this->addSql('ALTER TABLE balance DROP admin_id');

        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B783642B8210');
        $this->addSql('DROP INDEX IDX_389B783642B8210 ON tag');
        $this->addSql('ALTER TABLE tag DROP admin_id');

        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D03642B8210');
        $this->addSql('DROP INDEX IDX_C5D30D03642B8210 ON loan');
        $this->addSql('ALTER TABLE loan DROP admin_id');

        $this->addSql('ALTER TABLE investment DROP FOREIGN KEY FK_43CA0AD6642B8210');
        $this->addSql('DROP INDEX IDX_43CA0AD6642B8210 ON investment');
        $this->addSql('ALTER TABLE investment DROP admin_id');

        $this->addSql('ALTER TABLE configuration DROP FOREIGN KEY FK_A5E2A5D7642B8210');
        $this->addSql('DROP INDEX IDX_A5E2A5D7642B8210 ON configuration');
        $this->addSql('ALTER TABLE configuration DROP admin_id');

        $this->addSql('ALTER TABLE currency DROP FOREIGN KEY FK_6956883F642B8210');
        $this->addSql('DROP INDEX IDX_6956883F642B8210 ON currency');
        $this->addSql('ALTER TABLE currency DROP admin_id');
    }
}
