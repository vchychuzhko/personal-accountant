<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260609095606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE income ADD deposit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE income ADD type INT DEFAULT NULL');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D09815E4B1 FOREIGN KEY (deposit_id) REFERENCES deposit (id)');
        $this->addSql('CREATE INDEX IDX_3FA862D09815E4B1 ON income (deposit_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE income DROP FOREIGN KEY FK_3FA862D09815E4B1');
        $this->addSql('DROP INDEX IDX_3FA862D09815E4B1 ON income');
        $this->addSql('ALTER TABLE income DROP deposit_id');
        $this->addSql('ALTER TABLE income DROP type');
    }
}
