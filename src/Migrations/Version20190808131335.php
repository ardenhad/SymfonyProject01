<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190808131335 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE cart_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, price NUMERIC(10, 0) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cart_item_user (cart_item_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_9F5DB52DE9B59A59 (cart_item_id), INDEX IDX_9F5DB52DA76ED395 (user_id), PRIMARY KEY(cart_item_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cart_item_product (cart_item_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_AE98B090E9B59A59 (cart_item_id), INDEX IDX_AE98B0904584665A (product_id), PRIMARY KEY(cart_item_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cart_item_user ADD CONSTRAINT FK_9F5DB52DE9B59A59 FOREIGN KEY (cart_item_id) REFERENCES cart_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_item_user ADD CONSTRAINT FK_9F5DB52DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_item_product ADD CONSTRAINT FK_AE98B090E9B59A59 FOREIGN KEY (cart_item_id) REFERENCES cart_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_item_product ADD CONSTRAINT FK_AE98B0904584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product ADD quantity INT NOT NULL, ADD locked_quantity INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cart_item_user DROP FOREIGN KEY FK_9F5DB52DE9B59A59');
        $this->addSql('ALTER TABLE cart_item_product DROP FOREIGN KEY FK_AE98B090E9B59A59');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('DROP TABLE cart_item_user');
        $this->addSql('DROP TABLE cart_item_product');
        $this->addSql('ALTER TABLE product DROP quantity, DROP locked_quantity');
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677 ON user');
    }
}
