<?php
/**
 * Generates test database connectors.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

/**
 * Generates test database connectors.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Test 1.2.0
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Factory_Db
{
    /**
     * Create a connector to an in-memory sqlite DB.
     *
     * @params array $params Additional options.
     * <pre>
     * 'migrations' - (array) An list of migrations that should be run.
     *                Each element must contain the keys 'migrationsPath'
     *                and 'schemaTableName'.
     *                DEFAULT: empty
     * </pre>
     *
     * @return Horde_Db_Adapter_Pdo_Sqlite The DB adapter.
     */
    public function create($params = array())
    {
        if (!extension_loaded('pdo') ||
            !in_array('sqlite', PDO::getAvailableDrivers())) {
            throw new Horde_Test_Exception('No sqlite extension or no sqlite PDO driver');
        }
        if (!class_exists('Horde_Db_Adapter_Pdo_Sqlite')) {
            throw new Horde_Test_Exception('The "Horde_Db_Adapter_Pdo_Sqlite" class is unavailable!');
        }
        $db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:', 'charset' => 'utf-8'));
        if (isset($params['migrations'])) {
            if (isset($params['migrations']['migrationsPath'])) {
                $migrations = array($params['migrations']);
            } else {
                $migrations = $params['migrations'];
            }
            foreach ($migrations as $migration) {
                $migrator = new Horde_Db_Migration_Migrator(
                    $db, null, $migration
                );
                $migrator->up();
            }
        }
        return $db;
    }
}