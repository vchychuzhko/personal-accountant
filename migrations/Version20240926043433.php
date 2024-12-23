<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240926043433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE deposit (id INT AUTO_INCREMENT NOT NULL, balance_id INT NOT NULL, name VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, profit DOUBLE PRECISION DEFAULT NULL, status INT NOT NULL, interest DOUBLE PRECISION NOT NULL, tax DOUBLE PRECISION DEFAULT NULL, period INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, INDEX IDX_95DB9D39AE91A3DD (balance_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE loan (id INT AUTO_INCREMENT NOT NULL, currency_id INT NOT NULL, person VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C5D30D0338248176 (currency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE deposit ADD CONSTRAINT FK_95DB9D39AE91A3DD FOREIGN KEY (balance_id) REFERENCES balance (id)');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D0338248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deposit DROP FOREIGN KEY FK_95DB9D39AE91A3DD');
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D0338248176');
        $this->addSql('DROP TABLE deposit');
        $this->addSql('DROP TABLE loan');
    }
}
