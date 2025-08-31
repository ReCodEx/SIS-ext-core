<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250831104625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sis_affiliation DROP FOREIGN KEY FK_2A5DB2B0E2C35FC');
        $this->addSql('DROP INDEX IDX_2A5DB2B0E2C35FC ON sis_affiliation');
        $this->addSql('ALTER TABLE sis_affiliation DROP term_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sis_affiliation ADD term_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE sis_affiliation ADD CONSTRAINT FK_2A5DB2B0E2C35FC FOREIGN KEY (term_id) REFERENCES sis_term (id)');
        $this->addSql('CREATE INDEX IDX_2A5DB2B0E2C35FC ON sis_affiliation (term_id)');
    }
}
