<?php
/**
 * Install migration — creates access policy and principal tables.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperContentAccess\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * Install migration — creates access policy and principal tables.
 *
 * Policy rows are scoped by exactly one of:
 * - elementId (per-element)
 * - sectionId (channel default for entries)
 * - groupId (category group default)
 * - productTypeId (Commerce product type default)
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.0
 */
class Install extends Migration
{
    /**
     * Access policies table name.
     */
    public const TABLE_POLICIES = '{{%super_content_access_policies}}';

    /**
     * Policy principals table name.
     */
    public const TABLE_PRINCIPALS = '{{%super_content_access_principals}}';

    /**
     * Creates plugin tables, indexes, and foreign keys.
     *
     * @return bool True when the migration completes successfully.
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
     *
     * @return bool True when the migration completes successfully.
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(self::TABLE_PRINCIPALS);
        $this->dropTableIfExists(self::TABLE_POLICIES);

        return true;
    }

    /**
     * Creates the access policy and principal tables.
     *
     * @return void Nothing is returned.
     */
    protected function createTables(): void
    {
        $this->createTable(self::TABLE_POLICIES, [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->null(),
            'sectionId' => $this->integer()->null(),
            'groupId' => $this->integer()->null(),
            'productTypeId' => $this->integer()->null(),
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

    /**
     * Creates indexes for policy and principal lookups.
     *
     * @return void Nothing is returned.
     */
    protected function createIndexes(): void
    {
        $this->createIndex(null, self::TABLE_POLICIES, 'elementId', true);
        $this->createIndex(null, self::TABLE_POLICIES, 'sectionId', true);
        $this->createIndex(null, self::TABLE_POLICIES, 'groupId', true);
        $this->createIndex(null, self::TABLE_POLICIES, 'productTypeId', true);
        $this->createIndex(null, self::TABLE_PRINCIPALS, 'policyId', false);
        $this->createIndex(null, self::TABLE_PRINCIPALS, ['type', 'identifier'], false);
        $this->createIndex(null, self::TABLE_PRINCIPALS, ['policyId', 'type', 'identifier'], false);
    }

    /**
     * Adds foreign keys linking policies and principals to Craft tables.
     *
     * @return void Nothing is returned.
     */
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
            self::TABLE_POLICIES,
            'groupId',
            Table::CATEGORYGROUPS,
            'id',
            'CASCADE',
            null
        );

        // Soft dependency: only FK when Commerce product types table exists.
        if ($this->db->tableExists('{{%commerce_producttypes}}')) {
            $this->addForeignKey(
                null,
                self::TABLE_POLICIES,
                'productTypeId',
                '{{%commerce_producttypes}}',
                'id',
                'CASCADE',
                null
            );
        }

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
