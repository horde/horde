<?php
/**
 * A Horde_Injector:: based factory for creating PEAR DB objects.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based factory for creating PEAR DB objects..
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_DbPear extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the DB instance.
     *
     * @param string $type  Either 'read' or 'rw'.
     * @param string $app   The application.
     * @param mixed $dtype  The type. If this is an array, this is used as
     *                      the configuration array.
     *
     * @return DB  The singleton DB instance.
     * @throws Horde_Exception
     */
    public function create($type = 'rw', $app = 'horde', $dtype = null)
    {
        global $registry;

        $sig = hash('md5', serialize($type . '|' . $app . '|' . $dtype));

        if (isset($this->_instances[$sig])) {
            return $this->_instances[$sig];
        }

        $pushed = ($app == 'horde')
            ? false
            : $registry->pushApp($app);

        $config = is_array($dtype)
            ? $dtype
            : $this->getConfig($dtype);

        if ($type == 'read' && !empty($config['splitread'])) {
            $config = array_merge($config, $config['read']);
        }

        Horde::assertDriverConfig($config, 'sql', array('charset', 'phptype'));

        /* Connect to the SQL server using the supplied parameters. */
        $db = DB::connect($config, array(
            'persistent' => !empty($config['persistent']),
            'ssl' => !empty($config['ssl'])
        ));

        if ($db instanceof PEAR_Error) {
            if ($pushed) {
                $registry->popApp();
            }
            throw new Horde_Exception($db);
        }

        // Set DB portability options.
        $db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);

        if ($pushed) {
            $registry->popApp();
        }

        $this->_instances[$sig] = $db;

        return $db;
    }

    /**
     */
    public function getConfig($type)
    {
        return Horde::getDriverConfig($type, 'sql');
    }

}
