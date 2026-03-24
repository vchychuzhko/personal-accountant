<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260324072627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `admin` CHANGE username username VARCHAR(180) NOT NULL, CHANGE password password VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE income ADD investment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D06E1B4FD5 FOREIGN KEY (investment_id) REFERENCES investment (id)');
        $this->addSql('CREATE INDEX IDX_3FA862D06E1B4FD5 ON income (investment_id)');

        $this->addSql('ALTER TABLE `admin` RENAME INDEX uniq_identifier_username TO UNIQ_880E0D76F85E0677');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_43CA0AD65E237E06 ON investment (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `admin` CHANGE username username VARCHAR(180) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE password password VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE income DROP FOREIGN KEY FK_3FA862D06E1B4FD5');
        $this->addSql('DROP INDEX IDX_3FA862D06E1B4FD5 ON income');
        $this->addSql('ALTER TABLE income DROP investment_id');

        $this->addSql('ALTER TABLE `admin` RENAME INDEX uniq_880e0d76f85e0677 TO UNIQ_IDENTIFIER_USERNAME');
        $this->addSql('DROP INDEX UNIQ_43CA0AD65E237E06 ON investment');
    }
}
