<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231128174906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, team_id INT NOT NULL, volume INT NOT NULL, status VARCHAR(1) NOT NULL, date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', currency VARCHAR(3) NOT NULL, cost NUMERIC(10, 2) NOT NULL, note VARCHAR(255) DEFAULT NULL, tx_reference VARCHAR(32) DEFAULT NULL, issuer VARCHAR(32) DEFAULT NULL, method VARCHAR(1) NOT NULL, INDEX IDX_F5299398296CD8AE (team_id), INDEX IDX_F5299398AD7A4F15 (issuer), INDEX IDX_F5299398AA9E377A (date), INDEX IDX_F529939818CD4F2A (tx_reference), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pricing (id INT AUTO_INCREMENT NOT NULL, cost NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, active TINYINT(1) NOT NULL, volume INT NOT NULL, method VARCHAR(1) NOT NULL, UNIQUE INDEX UNIQ_E5F1AC336956883F5E593A60 (currency, method), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398296CD8AE');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE pricing');
    }
}
