<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313215151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD name VARCHAR(255) DEFAULT \'Utilisateur\' NOT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD last_connection DATETIME DEFAULT NULL, ADD failed_attempts INT DEFAULT 0 NOT NULL, ADD locked_until DATETIME DEFAULT NULL');
        // Remove temporary defaults
        $this->addSql('ALTER TABLE user ALTER name DROP DEFAULT, ALTER created_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP name, DROP created_at, DROP last_connection, DROP failed_attempts, DROP locked_until');
    }
}
