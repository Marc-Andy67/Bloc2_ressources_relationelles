<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260308220347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY `FK_D5B25073A76ED395`');
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY `FK_D5B25073FC6CD52A`');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_D5B25073A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_D5B25073FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_D5B25073FC6CD52A');
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_D5B25073A76ED395');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT `FK_D5B25073FC6CD52A` FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT `FK_D5B25073A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
