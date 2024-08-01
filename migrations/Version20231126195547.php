<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231126195547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign ADD status VARCHAR(1) NOT NULL, ADD contact JSON NOT NULL, DROP statut');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638F639F774');
        $this->addSql('DROP INDEX IDX_4C62E638F639F774 ON contact');
        $this->addSql('ALTER TABLE contact DROP campaign_id');
        $this->addSql('ALTER TABLE team ADD is_activated TINYINT(1) NOT NULL, CHANGE name name VARCHAR(60) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE type type VARCHAR(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team DROP is_activated, CHANGE name name VARCHAR(15) NOT NULL');
        $this->addSql('ALTER TABLE campaign ADD statut VARCHAR(15) NOT NULL, DROP status, DROP contact');
        $this->addSql('ALTER TABLE contact ADD campaign_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_4C62E638F639F774 ON contact (campaign_id)');
        $this->addSql('ALTER TABLE user CHANGE type type VARCHAR(15) NOT NULL');
    }
}
