<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250402135544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE session ADD id_workshop INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session ADD CONSTRAINT FK_D044D5D4CBB4EE51 FOREIGN KEY (id_workshop) REFERENCES workshop (id_workshop)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D044D5D4CBB4EE51 ON session (id_workshop)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4CBB4EE51
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D044D5D4CBB4EE51 ON session
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session DROP id_workshop
        SQL);
    }
}
