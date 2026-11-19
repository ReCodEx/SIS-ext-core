<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261119151124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sis_affiliation DROP FOREIGN KEY `FK_2A5DB2B071F7E88B`');
        $this->addSql('ALTER TABLE sis_affiliation ADD CONSTRAINT FK_2A5DB2B071F7E88B FOREIGN KEY (event_id) REFERENCES sis_schedule_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sis_schedule_event DROP FOREIGN KEY `FK_6C55C6CCE2C35FC`');
        $this->addSql('ALTER TABLE sis_schedule_event ADD CONSTRAINT FK_6C55C6CCE2C35FC FOREIGN KEY (term_id) REFERENCES sis_term (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sis_affiliation DROP FOREIGN KEY FK_2A5DB2B071F7E88B');
        $this->addSql('ALTER TABLE sis_affiliation ADD CONSTRAINT `FK_2A5DB2B071F7E88B` FOREIGN KEY (event_id) REFERENCES sis_schedule_event (id)');
        $this->addSql('ALTER TABLE sis_schedule_event DROP FOREIGN KEY FK_6C55C6CCE2C35FC');
        $this->addSql('ALTER TABLE sis_schedule_event ADD CONSTRAINT `FK_6C55C6CCE2C35FC` FOREIGN KEY (term_id) REFERENCES sis_term (id)');
    }
}
