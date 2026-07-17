<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717113247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sis_affiliation CHANGE user_id user_id CHAR(36) DEFAULT NULL, CHANGE event_id event_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE sis_course CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE sis_schedule_event CHANGE id id CHAR(36) NOT NULL, CHANGE term_id term_id CHAR(36) DEFAULT NULL, CHANGE course_id course_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE sis_term CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE id id CHAR(36) NOT NULL, CHANGE instance_id instance_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE user_changelog CHANGE user_id user_id CHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sis_affiliation CHANGE user_id user_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', CHANGE event_id event_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE sis_course CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE sis_schedule_event CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE term_id term_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', CHANGE course_id course_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE sis_term CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE user CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE instance_id instance_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE user_changelog CHANGE user_id user_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
    }
}
