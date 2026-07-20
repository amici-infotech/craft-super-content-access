<?php
/**
 * Adds a covering index for principal match lookups used by query authorization.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperContentAccess\migrations;

use craft\db\Migration;

/**
 * Speeds up the access anti-join: policyId + type + identifier.
 *
 * @author  Amici Infotech
 * @package SuperContentAccess
 * @since   5.0.4
 */
class m260720_070000_principals_covering_index extends Migration
{
    /**
     * Creates the covering index when missing.
     *
     * @return bool Whether the migration succeeded.
     */
    public function safeUp(): bool
    {
        $table = Install::TABLE_PRINCIPALS;
        $name = $this->db->getSchema()->getRawTableName($table);

        foreach ($this->db->getSchema()->getTableIndexes($name) as $index) {
            if (($index->columnNames ?? null) === ['policyId', 'type', 'identifier']) {
                return true;
            }
        }

        $this->createIndex(null, $table, ['policyId', 'type', 'identifier'], false);

        return true;
    }

    /**
     * Index drop is intentionally left as a no-op; the install schema keeps it.
     *
     * @return bool Whether the migration succeeded.
     */
    public function safeDown(): bool
    {
        return true;
    }
}
