<?php
/**
 * A factory for history handlers.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  History
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=History
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * The Horde_History_Factory:: provides a method for generating
 * a Horde_History handler.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  History
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=History
 */
class Horde_History_Factory
{
    /**
     * Creates a concrete Horde_History instance.
     *
     * @param Horde_Injector $injector  The environment for creating the
     *                                  instance.
     *
     * @return Horde_History The new Horde_History instance.
     *
     * @throws Horde_History_Exception If the injector provides no
     *                                 configuration.
     */
    static public function getHistory(Horde_Injector $injector)
    {
        try {
            $config = $injector->getInstance('Horde_History_Config');
        } catch (ReflectionException $e) {
            throw new Horde_History_Exception(
                sprintf(
                    'The configuration for the History driver is missing: %s',
                    $e->getMessage()
                )
            );
        }

        switch (ucfirst($config->driver)) {
        case 'Sql':
            return Horde_History_Factory::getHistorySql($injector, $config->params);
        case 'Mock':
            return Horde_History_Factory::getHistoryMock($config->params);
        default:
            throw new Horde_History_Exception(sprintf("Driver %s not supported!", $config->driver));
        }
    }

    /**
     * Creates a concrete Horde_History_Sql instance.
     *
     * @param Horde_Injector $injector  The environment for creating the
     *                                  instance.
     * @param array $params             The db connection parameters if the
     *                                  environment does not already provide a
     *                                  connection.
     *
     * @return Horde_History_Sql The new Horde_History_Sql instance.
     *
     * @throws Horde_History_Exception If the injector provides no
     *                                 configuration or creating the database
     *                                 connection failed.
     */
    static protected function getHistorySql(Horde_Injector $injector, array $params)
    {
        try {
            /* See if there is a specific write db instance available */
            $write_db = $injector->getInstance('DB_common_write');
            $history = new Horde_History_Sql($write_db);
            try {
                /* See if there is a specific read db instance available */
                $read_db = $injector->getInstance('DB_common_read');
                $history->setReadDb($read_db);
            } catch (ReflectionException $e) {
            }
        } catch (ReflectionException $e) {
            /* No DB instances. Use the configuration. */
            $write_db = Horde_History_Factory::getDb($params);

            $history = new Horde_History_Sql($write_db);

            /* Check if we need to set up the read DB connection
             * seperately. */
            if (!empty($params['splitread'])) {
                $params  = array_merge($params, $params['read']);
                $read_db = Horde_History_Factory::getDb($params);
                $history->setReadDb($read_db);
            }
        }
        return $history;
    }

    /**
     * Creates a concrete Horde_History_Mock instance.
     *
     * @param Horde_Injector $injector  The environment for creating the
     *                                  instance.
     * @param array $params             The db connection parameters if the
     *                                  environment does not already provide a
     *                                  connection.
     *
     * @return Horde_History_Mock The new Horde_History_Mock instance.
     *
     * @throws Horde_History_Exception If the injector provides no
     *                                 configuration or creating the database
     *                                 connection failed.
     */
    static protected function getHistoryMock(array $params)
    {
        return new Horde_History_Mock();
    }

    /**
     * Creates a database connection.
     *
     * @param array $params The database connection parameters.
     *
     * @return DB_common
     *
     * @throws Horde_History_Exception In case the database connection failed.
     */
    static protected function getDb(array $params)
    {
        $db = DB::connect($params);

        /* Set DB portability options. */
        $portability = DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS;

        if ($db instanceOf DB_common) {
            if ($db->phptype == 'mssql') {
                $portability |= DB_PORTABILITY_RTRIM;
            }
            $db->setOption('portability', $portability);
        }
        return $db;
    }
}
