<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260713094723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande_historique (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(100) NOT NULL, date_changement DATETIME NOT NULL, commentaire VARCHAR(255) DEFAULT NULL, commande_id INT NOT NULL, INDEX IDX_757DF90A82EA2E54 (commande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ROW_FORMAT = DYNAMIC');
        $this->addSql('ALTER TABLE commande_historique ADD CONSTRAINT FK_757DF90A82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_historique DROP FOREIGN KEY FK_757DF90A82EA2E54');
        $this->addSql('DROP TABLE commande_historique');
    }
}
