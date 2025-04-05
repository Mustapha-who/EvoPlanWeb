<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250404154828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE workshop ADD instructor INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workshop ADD CONSTRAINT FK_9B6F02C431FC43DD FOREIGN KEY (instructor) REFERENCES instructor (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9B6F02C431FC43DD ON workshop (instructor)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE workshop DROP FOREIGN KEY FK_9B6F02C431FC43DD
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_9B6F02C431FC43DD ON workshop
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE workshop DROP instructor
        SQL);
    }
}
