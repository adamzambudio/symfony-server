<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250529152258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE image DROP FOREIGN KEY image_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pay DROP FOREIGN KEY pay_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review DROP FOREIGN KEY review_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review DROP FOREIGN KEY review_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE image
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE pay
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reservation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE review
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE property CHANGE address address VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE cp cp INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE email email VARCHAR(180) NOT NULL, CHANGE roles roles JSON NOT NULL COMMENT '(DC2Type:json)', CHANGE picture picture VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX email ON user
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, property_id INT NOT NULL, url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX property_id (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE pay (id INT AUTO_INCREMENT NOT NULL, reservation_id INT DEFAULT NULL, amount DOUBLE PRECISION NOT NULL, method VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date DATETIME NOT NULL, UNIQUE INDEX reservation_id (reservation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, property_id INT NOT NULL, startDate DATETIME NOT NULL, endDate DATETIME NOT NULL, totalPrice DOUBLE PRECISION NOT NULL, INDEX user_id (user_id), INDEX property_id (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, property_id INT NOT NULL, rating INT NOT NULL, comment TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date DATETIME NOT NULL, INDEX user_id (user_id), INDEX property_id (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE image ADD CONSTRAINT image_ibfk_1 FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pay ADD CONSTRAINT pay_ibfk_1 FOREIGN KEY (reservation_id) REFERENCES reservation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_2 FOREIGN KEY (property_id) REFERENCES property (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_1 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review ADD CONSTRAINT review_ibfk_2 FOREIGN KEY (property_id) REFERENCES property (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review ADD CONSTRAINT review_ibfk_1 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE property CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE description description TEXT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE cp cp VARCHAR(10) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE email email VARCHAR(255) NOT NULL, CHANGE roles roles VARCHAR(255) NOT NULL, CHANGE picture picture VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX uniq_identifier_email ON user
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX email ON user (email)
        SQL);
    }
}
