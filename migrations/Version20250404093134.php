<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250404093134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD lieu VARCHAR(255) NOT NULL, ADD statut VARCHAR(255) NOT NULL, CHANGE nombre_visites nombre_visites INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workshop CHANGE description description LONGTEXT NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE workshop CHANGE description description TEXT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP lieu, DROP statut, CHANGE nombre_visites nombre_visites INT DEFAULT NULL
        SQL);
    }
}
