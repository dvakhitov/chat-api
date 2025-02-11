<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250211124434 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Обновляем поле chat_index для каждого чата:
        // Для каждого чата собираем идентификаторы пользователей из таблицы chat_partner,
        // сортируем их по возрастанию и объединяем через нижнее подчеркивание.
        $this->addSql(<<<SQL
            UPDATE chat
            SET chat_index = (
                SELECT string_agg(cp.user_id::text, '_' ORDER BY cp.user_id)
                FROM chat_partner cp
                WHERE cp.chat_id = chat.id
            )
SQL
        );
    }

    public function down(Schema $schema): void
    {
        // При откате обнуляем поле chat_index
        $this->addSql("UPDATE chat SET chat_index = NULL");
    }
}
