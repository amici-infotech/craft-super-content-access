<?php
namespace amici\SuperContentAccess\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * Install migration — creates access policy and principal tables.
 */
class Install extends Migration
{
    public const TABLE_POLICIES = '{{%super_content_access_policies}}';
    public const TABLE_PRINCIPALS = '{{%super_content_access_principals}}';

    /**
     * Creates plugin tables, indexes, and foreign keys.
     */
    public function safeUp(): bool
    {
        if ($this->db->tableExists(self::TABLE_POLICIES)) {
            return true;
        }

        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    /**
     * Drops plugin tables.
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(self::TABLE_PRINCIPALS);
        $this->dropTableIfExists(self::TABLE_POLICIES);

        return true;
    }

    protected function createTables(): void
    {
        $this->createTable(self::TABLE_POLICIES, [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->null(),
            'sectionId' => $this->integer()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable(self::TABLE_PRINCIPALS, [
            'id' => $this->primaryKey(),
            'policyId' => $this->integer()->notNull(),
            'type' => $this->string(32)->notNull(),
            'identifier' => $this->string(255)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    protected function createIndexes(): void
    {
        $this->createIndex(null, self::TABLE_POLICIES, 'elementId', true);
        $this->createIndex(null, self::TABLE_POLICIES, 'sectionId', true);
        $this->createIndex(null, self::TABLE_PRINCIPALS, 'policyId', false);
        $this->createIndex(null, self::TABLE_PRINCIPALS, ['type', 'identifier'], false);
        $this->createIndex(null, self::TABLE_PRINCIPALS, ['policyId', 'type', 'identifier'], false);
    }

    protected function addForeignKeys(): void
    {
        $this->addForeignKey(
            null,
            self::TABLE_POLICIES,
            'elementId',
            Table::ELEMENTS,
            'id',
            'CASCADE',
            null
        );

        $this->addForeignKey(
            null,
            self::TABLE_POLICIES,
            'sectionId',
            Table::SECTIONS,
            'id',
            'CASCADE',
            null
        );

        $this->addForeignKey(
            null,
            self::TABLE_PRINCIPALS,
            'policyId',
            self::TABLE_POLICIES,
            'id',
            'CASCADE',
            null
        );
    }
}
