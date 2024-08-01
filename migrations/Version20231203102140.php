<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231203102140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE campaign_tracking_info (id INT AUTO_INCREMENT NOT NULL, campaign_id INT NOT NULL, recipient VARCHAR(15) NOT NULL, status VARCHAR(1) NOT NULL, tracking_id VARCHAR(255) DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, INDEX IDX_589A0EE8F639F774 (campaign_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campaign_tracking_info ADD CONSTRAINT FK_589A0EE8F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id)');
        $this->addSql('ALTER TABLE campaign ADD provider VARCHAR(20) DEFAULT NULL, ADD tracking_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE team ADD sender VARCHAR(15) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign_tracking_info DROP FOREIGN KEY FK_589A0EE8F639F774');
        $this->addSql('DROP TABLE campaign_tracking_info');
        $this->addSql('ALTER TABLE team DROP sender');
        $this->addSql('ALTER TABLE campaign DROP provider, DROP tracking_id');
    }
}
