<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225093544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chat_message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, creation_date DATE NOT NULL, chat_room_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_FAB3FC161819BCFA (chat_room_id), INDEX IDX_FAB3FC16F675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chat_room (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, ressource_id INT DEFAULT NULL, INDEX IDX_D403CCDAFC6CD52A (ressource_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chat_room_user (chat_room_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_C87A2E561819BCFA (chat_room_id), INDEX IDX_C87A2E56A76ED395 (user_id), PRIMARY KEY (chat_room_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, creation_date DATE NOT NULL, parent_id INT DEFAULT NULL, ressource_id INT DEFAULT NULL, author_id INT NOT NULL, INDEX IDX_9474526C727ACA70 (parent_id), INDEX IDX_9474526CFC6CD52A (ressource_id), INDEX IDX_9474526CF675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE progression (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) DEFAULT NULL, date DATE DEFAULT NULL, ressource_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_D5B25073FC6CD52A (ressource_id), INDEX IDX_D5B25073A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE relation_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ressource (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, creation_date DATE NOT NULL, status TINYINT NOT NULL, size INT DEFAULT NULL, category_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_939F454412469DE2 (category_id), INDEX IDX_939F4544F675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ressource_relation_type (ressource_id INT NOT NULL, relation_type_id INT NOT NULL, INDEX IDX_32ADC4E0FC6CD52A (ressource_id), INDEX IDX_32ADC4E0DC379EE2 (relation_type_id), PRIMARY KEY (ressource_id, relation_type_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ressource_favorite (ressource_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_26743703FC6CD52A (ressource_id), INDEX IDX_26743703A76ED395 (user_id), PRIMARY KEY (ressource_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ressource_set_aside (ressource_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_920C1B5CFC6CD52A (ressource_id), INDEX IDX_920C1B5CA76ED395 (user_id), PRIMARY KEY (ressource_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ressource_liked (ressource_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5E8D4F3EFC6CD52A (ressource_id), INDEX IDX_5E8D4F3EA76ED395 (user_id), PRIMARY KEY (ressource_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC161819BCFA FOREIGN KEY (chat_room_id) REFERENCES chat_room (id)');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16F675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE chat_room ADD CONSTRAINT FK_D403CCDAFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('ALTER TABLE chat_room_user ADD CONSTRAINT FK_C87A2E561819BCFA FOREIGN KEY (chat_room_id) REFERENCES chat_room (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_room_user ADD CONSTRAINT FK_C87A2E56A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C727ACA70 FOREIGN KEY (parent_id) REFERENCES comment (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_D5B25073FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_D5B25073A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ressource ADD CONSTRAINT FK_939F454412469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE ressource ADD CONSTRAINT FK_939F4544F675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ressource_relation_type ADD CONSTRAINT FK_32ADC4E0FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ressource_relation_type ADD CONSTRAINT FK_32ADC4E0DC379EE2 FOREIGN KEY (relation_type_id) REFERENCES relation_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ressource_favorite ADD CONSTRAINT FK_26743703FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ressource_favorite ADD CONSTRAINT FK_26743703A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ressource_set_aside ADD CONSTRAINT FK_920C1B5CFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ressource_set_aside ADD CONSTRAINT FK_920C1B5CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ressource_liked ADD CONSTRAINT FK_5E8D4F3EFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ressource_liked ADD CONSTRAINT FK_5E8D4F3EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC161819BCFA');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16F675F31B');
        $this->addSql('ALTER TABLE chat_room DROP FOREIGN KEY FK_D403CCDAFC6CD52A');
        $this->addSql('ALTER TABLE chat_room_user DROP FOREIGN KEY FK_C87A2E561819BCFA');
        $this->addSql('ALTER TABLE chat_room_user DROP FOREIGN KEY FK_C87A2E56A76ED395');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C727ACA70');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CFC6CD52A');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CF675F31B');
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_D5B25073FC6CD52A');
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_D5B25073A76ED395');
        $this->addSql('ALTER TABLE ressource DROP FOREIGN KEY FK_939F454412469DE2');
        $this->addSql('ALTER TABLE ressource DROP FOREIGN KEY FK_939F4544F675F31B');
        $this->addSql('ALTER TABLE ressource_relation_type DROP FOREIGN KEY FK_32ADC4E0FC6CD52A');
        $this->addSql('ALTER TABLE ressource_relation_type DROP FOREIGN KEY FK_32ADC4E0DC379EE2');
        $this->addSql('ALTER TABLE ressource_favorite DROP FOREIGN KEY FK_26743703FC6CD52A');
        $this->addSql('ALTER TABLE ressource_favorite DROP FOREIGN KEY FK_26743703A76ED395');
        $this->addSql('ALTER TABLE ressource_set_aside DROP FOREIGN KEY FK_920C1B5CFC6CD52A');
        $this->addSql('ALTER TABLE ressource_set_aside DROP FOREIGN KEY FK_920C1B5CA76ED395');
        $this->addSql('ALTER TABLE ressource_liked DROP FOREIGN KEY FK_5E8D4F3EFC6CD52A');
        $this->addSql('ALTER TABLE ressource_liked DROP FOREIGN KEY FK_5E8D4F3EA76ED395');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE chat_message');
        $this->addSql('DROP TABLE chat_room');
        $this->addSql('DROP TABLE chat_room_user');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE progression');
        $this->addSql('DROP TABLE relation_type');
        $this->addSql('DROP TABLE ressource');
        $this->addSql('DROP TABLE ressource_relation_type');
        $this->addSql('DROP TABLE ressource_favorite');
        $this->addSql('DROP TABLE ressource_set_aside');
        $this->addSql('DROP TABLE ressource_liked');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
