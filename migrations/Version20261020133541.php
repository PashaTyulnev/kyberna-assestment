<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261020133541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE book (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, isbn VARCHAR(255) NOT NULL, available_copies INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE loan (id INT AUTO_INCREMENT NOT NULL, loaned_at DATETIME NOT NULL, due_at DATETIME NOT NULL, returned_at DATETIME DEFAULT NULL, book_id INT NOT NULL, member_id INT NOT NULL, INDEX IDX_C5D30D0316A2B381 (book_id), INDEX IDX_C5D30D037597D3FE (member_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE member (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, membership_date DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D0316A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D037597D3FE FOREIGN KEY (member_id) REFERENCES member (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D0316A2B381');
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D037597D3FE');
        $this->addSql('DROP TABLE book');
        $this->addSql('DROP TABLE loan');
        $this->addSql('DROP TABLE member');
    }
}
