<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250525220626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ingredients ADD name VARCHAR(255) NOT NULL, ADD quantity VARCHAR(255) NOT NULL, CHANGE id id VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recipes ADD name VARCHAR(255) NOT NULL, ADD description VARCHAR(255) NOT NULL, ADD image_url VARCHAR(255) NOT NULL, ADD servings VARCHAR(255) NOT NULL, ADD cooking_time VARCHAR(255) NOT NULL, ADD created_at VARCHAR(255) NOT NULL, ADD user CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)', CHANGE id id VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD username VARCHAR(255) NOT NULL, ADD email VARCHAR(255) NOT NULL, ADD password VARCHAR(255) NOT NULL, CHANGE id id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE userrecipes ADD user CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)', ADD recipes CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ingredients DROP name, DROP quantity, CHANGE id id INT AUTO_INCREMENT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recipes DROP name, DROP description, DROP image_url, DROP servings, DROP cooking_time, DROP created_at, DROP user, CHANGE id id INT AUTO_INCREMENT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP username, DROP email, DROP password, CHANGE id id INT AUTO_INCREMENT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE userrecipes DROP user, DROP recipes
        SQL);
    }
}
