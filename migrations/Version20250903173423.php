<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250903173423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sis_schedule_event CHANGE day_of_week day_of_week INT DEFAULT NULL, CHANGE first_week first_week INT DEFAULT NULL, CHANGE time time INT DEFAULT NULL, CHANGE length length INT DEFAULT NULL, CHANGE room room VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sis_schedule_event CHANGE day_of_week day_of_week INT NOT NULL, CHANGE first_week first_week INT NOT NULL, CHANGE time time INT NOT NULL, CHANGE length length INT NOT NULL, CHANGE room room VARCHAR(255) NOT NULL');
    }
}
