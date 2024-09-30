<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240930080821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exchange (id INT AUTO_INCREMENT NOT NULL, balance_from_id INT NOT NULL, balance_to_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, result DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D33BB0794C25FBB1 (balance_from_id), INDEX IDX_D33BB079AE43FB37 (balance_to_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE income (id INT AUTO_INCREMENT NOT NULL, balance_id INT NOT NULL, name VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3FA862D0AE91A3DD (balance_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, balance_id INT NOT NULL, tag_id INT NOT NULL, name VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6D28840DAE91A3DD (balance_id), INDEX IDX_6D28840DBAD26311 (tag_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE exchange ADD CONSTRAINT FK_D33BB0794C25FBB1 FOREIGN KEY (balance_from_id) REFERENCES balance (id)');
        $this->addSql('ALTER TABLE exchange ADD CONSTRAINT FK_D33BB079AE43FB37 FOREIGN KEY (balance_to_id) REFERENCES balance (id)');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D0AE91A3DD FOREIGN KEY (balance_id) REFERENCES balance (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DAE91A3DD FOREIGN KEY (balance_id) REFERENCES balance (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exchange DROP FOREIGN KEY FK_D33BB0794C25FBB1');
        $this->addSql('ALTER TABLE exchange DROP FOREIGN KEY FK_D33BB079AE43FB37');
        $this->addSql('ALTER TABLE income DROP FOREIGN KEY FK_3FA862D0AE91A3DD');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DAE91A3DD');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DBAD26311');
        $this->addSql('DROP TABLE exchange');
        $this->addSql('DROP TABLE income');
        $this->addSql('DROP TABLE payment');
    }
}
