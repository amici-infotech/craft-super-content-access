<?php
/**
 * Adds section (channel) scope to access policies.
 *
 * A policy row is now either element-scoped (elementId set) or section-scoped
 * (sectionId set). Section-scoped policies act as the default "general access"
 * for every entry in that channel that has no element-level policy.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperContentAccess\migrations;

use amici\SuperContentAccess\migrations\Install;
use craft\db\Migration;
use craft\db\Table;

/**
 * Makes elementId nullable and adds a nullable sectionId scope column.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.3
 */
class m260716_120000_add_section_scope extends Migration
{
    /**
     * Applies the section-scope schema changes.
     *
     * @return bool Whether the migration succeeded.
     */
    public function safeUp(): bool
    {
        $table = Install::TABLE_POLICIES;

        // Element-scoped policies keep their FK, but section rows leave it null.
        $this->alterColumn($table, 'elementId', $this->integer()->null());

        $schema = $this->db->getTableSchema($table);
        if ($schema !== null && $schema->getColumn('sectionId') === null) {
            $this->addColumn($table, 'sectionId', $this->integer()->null()->after('elementId'));
            $this->createIndex(null, $table, 'sectionId', true);
            $this->addForeignKey(null, $table, 'sectionId', Table::SECTIONS, 'id', 'CASCADE', null);
        }

        return true;
    }

    /**
     * Reverts the section-scope schema changes.
     *
     * @return bool Whether the migration succeeded.
     */
    public function safeDown(): bool
    {
        $table = Install::TABLE_POLICIES;

        $schema = $this->db->getTableSchema($table);
        if ($schema !== null && $schema->getColumn('sectionId') !== null) {
            $this->dropColumn($table, 'sectionId');
        }

        return true;
    }
}
