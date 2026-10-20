<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261020150102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reminders (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, overdue_loan_id INT NOT NULL, INDEX IDX_6D92B9D431E5DC5A (overdue_loan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE reminders ADD CONSTRAINT FK_6D92B9D431E5DC5A FOREIGN KEY (overdue_loan_id) REFERENCES loan (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reminders DROP FOREIGN KEY FK_6D92B9D431E5DC5A');
        $this->addSql('DROP TABLE reminders');
    }
}
