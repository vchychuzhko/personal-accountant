<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251003201428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE investment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, share DOUBLE PRECISION NOT NULL, currency_id INT NOT NULL, price DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `admin` CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE payment ADD investment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D6E1B4FD5 FOREIGN KEY (investment_id) REFERENCES investment (id)');
        $this->addSql('CREATE INDEX IDX_6D28840D6E1B4FD5 ON payment (investment_id)');
        $this->addSql('ALTER TABLE investment ADD CONSTRAINT FK_43CA0AD638248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('CREATE INDEX IDX_43CA0AD638248176 ON investment (currency_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D6E1B4FD5');
        $this->addSql('ALTER TABLE investment DROP FOREIGN KEY FK_43CA0AD638248176');
        $this->addSql('DROP TABLE investment');
        $this->addSql('DROP INDEX IDX_6D28840D6E1B4FD5 ON payment');
        $this->addSql('ALTER TABLE payment DROP investment_id');
        $this->addSql('DROP INDEX IDX_43CA0AD638248176 ON investment');
        $this->addSql('ALTER TABLE `admin` CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
    }
}
