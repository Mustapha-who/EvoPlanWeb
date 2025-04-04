<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250403102433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE workshop ADD id_event INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workshop ADD CONSTRAINT FK_9B6F02C4D52B4B97 FOREIGN KEY (id_event) REFERENCES event (id_event)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9B6F02C4D52B4B97 ON workshop (id_event)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE workshop DROP FOREIGN KEY FK_9B6F02C4D52B4B97
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_9B6F02C4D52B4B97 ON workshop
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workshop DROP id_event
        SQL);
    }
}
