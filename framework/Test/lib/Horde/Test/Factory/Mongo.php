<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 * @package  Test
 */

/**
 * Generates test MongoDB database.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @ignore
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @link      http://www.horde.org/components/Horde_Test
 * @package   Test
 */
class Horde_Test_Factory_Mongo
{
    const DEFAULT_DB = 'horde_mongo_testdb';

    /**
     * Create a connector to a temporary MongoDB instance.
     *
     * @param array $params  Additional options:
     * <pre>
     *   - config: (array) Configuration for Horde_Mongo_Client.
     *   - dbname: (string) Database name to use.
     * </pre>
     *
     * @return Horde_Mongo_Client|null  The DB object.
     */
    public function create(array $params = array())
    {
        $mongo = null;

        if (extension_loaded('mongo') &&
            class_exists('Horde_Mongo_Client') &&
            !empty($params['config'])) {
            try {
                $mongo = new Horde_Mongo_Client($params['config']);
                $mongo->dbname = isset($params['dbname'])
                    ? $params['dbname']
                    : self::DEFAULT_DB;
                $mongo->selectDB(null)->drop();
            } catch (Exception $e) {}
        }

        return $mongo;
    }

}
